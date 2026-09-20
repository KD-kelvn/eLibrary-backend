<?php

namespace Modules\BookCatalogue\Services;

use Illuminate\Support\Facades\Storage;
use Modules\BookCatalogue\Enums\BookTypeEnum;
use Modules\BookCatalogue\Enums\EbookFormatEnum;
use Modules\BookCatalogue\Exceptions\BookCatalogueException;
use Modules\BookCatalogue\Models\BookDetail;
use Modules\BookReading\Models\ReadingHistory;
use Modules\BookReading\Services\ReadingHistoryService;
use ZipArchive;

/**
 * Serve a single book page and record reading progress.
 * Full ebook files are never returned by this API.
 */
class BookPageService
{
    public function __construct(
        protected EbookMetadataService $metadata,
        protected ReadingHistoryService $readingHistory,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getPage(BookDetail $book, int $page, int $userId): array
    {
        if ($book->type_code !== BookTypeEnum::Digital) {
            throw BookCatalogueException::forbidden('Only digital books support page streaming.');
        }

        $digital = $book->digitalBook;
        if (! $digital || ! $digital->is_active) {
            throw BookCatalogueException::notFound('Digital book');
        }

        $format = $digital->format instanceof EbookFormatEnum
            ? $digital->format
            : EbookFormatEnum::tryFrom((string) $digital->format) ?? EbookFormatEnum::Pdf;

        $payload = $this->extractPagePayload($digital->file_path, $format, $page);
        $totalPages = max(1, (int) ($payload['totalPages'] ?? $book->pages));

        if ($page < 1 || $page > $totalPages) {
            throw BookCatalogueException::unprocessable("Page must be between 1 and {$totalPages}.");
        }

        if (! ($payload['contentAvailable'] ?? false)) {
            // Still track progress even when binary content is pending (e.g. PDF).
        }

        $progress = round(($page / $totalPages) * 100, 2);

        $openHistory = ReadingHistory::query()
            ->where('user_id', $userId)
            ->where('book_detail_id', $book->id)
            ->whereNull('completed_at')
            ->latest('last_read_at')
            ->first();

        $history = $this->readingHistory->track($userId, [
            'history_id' => $openHistory?->id,
            'book_detail_id' => $book->id,
            'progress_percent' => $progress,
            'current_location' => "page:{$page}",
            'completed' => $page >= $totalPages,
        ]);

        return [
            'bookId' => $book->id,
            'page' => $page,
            'totalPages' => $totalPages,
            'format' => $format->value,
            'contentType' => $payload['contentType'],
            'content' => $payload['content'],
            'contentAvailable' => (bool) ($payload['contentAvailable'] ?? false),
            'historyId' => $history->id,
            'progressPercent' => $progress,
        ];
    }

    /**
     * Open the first meaningful content page for reading.
     *
     * @return array<string, mixed>
     */
    public function openForReading(BookDetail $book, int $userId): array
    {
        if ($book->type_code !== BookTypeEnum::Digital) {
            throw BookCatalogueException::forbidden('Only digital books support page streaming.');
        }

        $digital = $book->digitalBook;
        if (! $digital || ! $digital->is_active) {
            throw BookCatalogueException::notFound('Digital book');
        }

        $format = $digital->format instanceof EbookFormatEnum
            ? $digital->format
            : EbookFormatEnum::tryFrom((string) $digital->format) ?? EbookFormatEnum::Pdf;

        $startPage = $this->firstReadingPage($digital->file_path, $format);

        return $this->getPage($book, $startPage, $userId);
    }

    /**
     * First linear reading page (skips cover/nav marked linear="no").
     */
    public function firstReadingPage(string $storedPath, EbookFormatEnum $format): int
    {
        if ($format !== EbookFormatEnum::Epub) {
            return 1;
        }

        $absolute = Storage::disk('public')->path($storedPath);
        if (! is_file($absolute)) {
            return 1;
        }

        $zip = new ZipArchive;
        if ($zip->open($absolute) !== true) {
            return 1;
        }

        try {
            $spine = $this->resolveEpubSpine($zip, linearOnly: true);
            foreach ($spine as $index => $entry) {
                $html = $zip->getFromName($entry['path']);
                if ($html === false) {
                    continue;
                }
                $text = trim(preg_replace('/\s+/', ' ', strip_tags($html)) ?? '');
                $isImageHeavy = (bool) preg_match('/<(?:img|image)\b/i', $html) &&
                    strlen($text) < 120;
                if ($isImageHeavy) {
                    continue;
                }
                if (strlen($text) >= 40) {
                    return $index + 1;
                }
            }

            return 1;
        } finally {
            $zip->close();
        }
    }

    /**
     * @return array{contentType: string, content: string|null, contentAvailable: bool, totalPages?: int}
     */
    protected function extractPagePayload(string $storedPath, EbookFormatEnum $format, int $page): array
    {
        $absolute = Storage::disk('public')->path($storedPath);

        if (! is_file($absolute)) {
            return [
                'contentType' => $format->mimeType(),
                'content' => null,
                'contentAvailable' => false,
            ];
        }

        return match ($format) {
            EbookFormatEnum::Epub => $this->extractEpubSpineItem($absolute, $page),
            EbookFormatEnum::Pdf => [
                'contentType' => 'application/pdf',
                'content' => null,
                'contentAvailable' => false,
            ],
            EbookFormatEnum::Mobi => [
                'contentType' => $format->mimeType(),
                'content' => null,
                'contentAvailable' => false,
            ],
        };
    }

    /**
     * @return array{contentType: string, content: string|null, contentAvailable: bool, totalPages: int}
     */
    protected function extractEpubSpineItem(string $absolutePath, int $page): array
    {
        $zip = new ZipArchive;
        if ($zip->open($absolutePath) !== true) {
            return [
                'contentType' => 'text/html',
                'content' => null,
                'contentAvailable' => false,
                'totalPages' => 1,
            ];
        }

        try {
            $spine = $this->resolveEpubSpine($zip, linearOnly: true);
            $totalPages = max(1, count($spine));

            if ($page < 1 || $page > $totalPages) {
                return [
                    'contentType' => 'text/html',
                    'content' => null,
                    'contentAvailable' => false,
                    'totalPages' => $totalPages,
                ];
            }

            $entry = $spine[$page - 1];
            $raw = $zip->getFromName($entry['path']);
            if ($raw === false) {
                return [
                    'contentType' => 'text/html',
                    'content' => null,
                    'contentAvailable' => false,
                    'totalPages' => $totalPages,
                ];
            }

            $html = $this->prepareEpubHtml($zip, $raw, $entry['path']);

            return [
                'contentType' => 'text/html',
                'content' => $html,
                'contentAvailable' => $html !== '',
                'totalPages' => $totalPages,
            ];
        } finally {
            $zip->close();
        }
    }

    /**
     * @return list<array{idref: string, path: string}>
     */
    protected function resolveEpubSpine(ZipArchive $zip, bool $linearOnly = true): array
    {
        $container = $zip->getFromName('META-INF/container.xml');
        if ($container === false || ! preg_match('/full-path="([^"]+)"/', $container, $match)) {
            return [];
        }

        $opfPath = $match[1];
        $opfDir = trim(str_replace('\\', '/', dirname($opfPath)), '.');
        $opf = $zip->getFromName($opfPath);
        if ($opf === false) {
            return [];
        }

        preg_match_all(
            '/<item\b[^>]*>/i',
            $opf,
            $itemTags,
        );
        $hrefById = [];
        foreach ($itemTags[0] as $tag) {
            if (! preg_match('/\bid=["\']([^"\']+)["\']/i', $tag, $idMatch)) {
                continue;
            }
            if (! preg_match('/\bhref=["\']([^"\']+)["\']/i', $tag, $hrefMatch)) {
                continue;
            }
            $hrefById[$idMatch[1]] = html_entity_decode($hrefMatch[1], ENT_QUOTES | ENT_XML1);
        }

        preg_match_all('/<itemref\b([^>]*)>/i', $opf, $spineTags);
        $spine = [];
        foreach ($spineTags[1] as $attrs) {
            if ($linearOnly && preg_match('/\blinear=["\']no["\']/i', $attrs)) {
                continue;
            }
            if (! preg_match('/\bidref=["\']([^"\']+)["\']/i', $attrs, $idrefMatch)) {
                continue;
            }
            $idref = $idrefMatch[1];
            $href = $hrefById[$idref] ?? null;
            if (! $href) {
                continue;
            }
            $path = $this->joinZipPath($opfDir, $href);
            $spine[] = [
                'idref' => $idref,
                'path' => $path,
            ];
        }

        return $spine;
    }

    protected function prepareEpubHtml(ZipArchive $zip, string $raw, string $chapterPath): string
    {
        $html = preg_replace('/<\?xml[^?]*\?>/i', '', $raw) ?? $raw;

        if (preg_match('/<body[^>]*>(.*)<\/body>/is', $html, $bodyMatch)) {
            $html = $bodyMatch[1];
        }

        $baseDir = trim(str_replace('\\', '/', dirname($chapterPath)), '.');

        $inlineAsset = function (string $src) use ($zip, $baseDir): ?string {
            $src = html_entity_decode(trim($src), ENT_QUOTES | ENT_XML1);
            if ($src === '' || str_starts_with($src, 'data:') || preg_match('#^(https?:)?//#i', $src)) {
                return null;
            }

            $assetPath = $this->joinZipPath($baseDir, rawurldecode($src));
            $bytes = $this->readZipEntry($zip, $assetPath);
            if ($bytes === false) {
                return null;
            }

            $mime = $this->guessMimeType($assetPath);

            return 'data:'.$mime.';base64,'.base64_encode($bytes);
        };

        $html = preg_replace_callback(
            '/(<img\b[^>]*\bsrc=["\'])([^"\']+)(["\'])/i',
            function (array $match) use ($inlineAsset) {
                $dataUri = $inlineAsset($match[2]);

                return $dataUri ? $match[1].$dataUri.$match[3] : $match[0];
            },
            $html,
        ) ?? $html;

        $html = preg_replace_callback(
            '/(<image\b[^>]*\b(?:xlink:)?href=["\'])([^"\']+)(["\'])/i',
            function (array $match) use ($inlineAsset) {
                $dataUri = $inlineAsset($match[2]);

                return $dataUri ? $match[1].$dataUri.$match[3] : $match[0];
            },
            $html,
        ) ?? $html;

        // Soften absolute-positioned EPUB layouts for the preview pane.
        return '<div class="epub-page" style="color:inherit;line-height:1.6;max-width:100%;overflow-wrap:anywhere;">'
            .$html
            .'</div>';
    }

    /**
     * @return string|false
     */
    protected function readZipEntry(ZipArchive $zip, string $path): string|false
    {
        $candidates = array_values(array_unique([
            $path,
            ltrim($path, '/'),
            rawurldecode($path),
            ltrim(rawurldecode($path), '/'),
        ]));

        foreach ($candidates as $candidate) {
            $bytes = $zip->getFromName($candidate);
            if ($bytes !== false) {
                return $bytes;
            }
        }

        // Fallback: match by basename (covers odd OPF folder layouts).
        $basename = basename(str_replace('\\', '/', $path));
        if ($basename === '' || $basename === '.' || $basename === '..') {
            return false;
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if ($name === false) {
                continue;
            }
            if (strcasecmp(basename($name), $basename) === 0) {
                $bytes = $zip->getFromIndex($i);
                if ($bytes !== false) {
                    return $bytes;
                }
            }
        }

        return false;
    }

    protected function joinZipPath(string $baseDir, string $relative): string
    {
        $relative = str_replace('\\', '/', $relative);
        if ($baseDir === '' || $baseDir === '.') {
            return ltrim($relative, '/');
        }

        $parts = explode('/', $baseDir.'/'.ltrim($relative, '/'));
        $stack = [];
        foreach ($parts as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..') {
                array_pop($stack);
                continue;
            }
            $stack[] = $part;
        }

        return implode('/', $stack);
    }

    protected function guessMimeType(string $path): string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            default => 'application/octet-stream',
        };
    }
}

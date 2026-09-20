<?php

namespace Modules\BookCatalogue\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\BookCatalogue\Enums\EbookFormatEnum;
use ZipArchive;

/**
 * Derive ebook format and page count from an uploaded/stored file.
 * Used so librarians never enter file path/name/type/size/pages manually.
 */
class EbookMetadataService
{
    /**
     * @return array{
     *     format: EbookFormatEnum,
     *     pages: int,
     *     mime: string,
     *     extension: string
     * }
     */
    public function inspectUpload(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: '');
        $mime = $file->getMimeType() ?? $file->getClientMimeType() ?? 'application/octet-stream';
        $format = $this->resolveFormat($extension, $mime);
        $absolute = $file->getRealPath() ?: $file->getPathname();

        return [
            'format' => $format,
            'pages' => max(1, $this->countPages($absolute, $format)),
            'mime' => $mime,
            'extension' => $extension,
        ];
    }

    /**
     * @return array{format: EbookFormatEnum, pages: int}
     */
    public function inspectStored(string $path, string $disk = 'public'): array
    {
        $absolute = Storage::disk($disk)->path($path);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $format = $this->resolveFormat($extension, '');
        $pages = is_file($absolute)
            ? max(1, $this->countPages($absolute, $format))
            : 1;

        return [
            'format' => $format,
            'pages' => $pages,
        ];
    }

    public function resolveFormat(string $extension, string $mime): EbookFormatEnum
    {
        if ($extension === 'epub' || str_contains($mime, 'epub')) {
            return EbookFormatEnum::Epub;
        }

        if ($extension === 'mobi' || str_contains($mime, 'mobipocket')) {
            return EbookFormatEnum::Mobi;
        }

        return EbookFormatEnum::Pdf;
    }

    public function countPages(string $absolutePath, EbookFormatEnum $format): int
    {
        if (! is_file($absolutePath)) {
            return 1;
        }

        return match ($format) {
            EbookFormatEnum::Epub => $this->countEpubSpineItems($absolutePath),
            EbookFormatEnum::Pdf => $this->countPdfPages($absolutePath),
            EbookFormatEnum::Mobi => 1,
        };
    }

    protected function countPdfPages(string $absolutePath): int
    {
        $content = @file_get_contents($absolutePath);
        if ($content === false || $content === '') {
            return 1;
        }

        if (preg_match_all('/\/Type\s*\/Page(?!s)\b/', $content, $matches)) {
            $count = count($matches[0]);
            if ($count > 0) {
                return $count;
            }
        }

        if (preg_match('/\/Count\s+(\d+)/', $content, $match)) {
            return max(1, (int) $match[1]);
        }

        return 1;
    }

    protected function countEpubSpineItems(string $absolutePath): int
    {
        $zip = new ZipArchive;
        if ($zip->open($absolutePath) !== true) {
            return 1;
        }

        try {
            $container = $zip->getFromName('META-INF/container.xml');
            if ($container === false) {
                return 1;
            }

            $opfPath = null;
            if (preg_match('/full-path="([^"]+)"/', $container, $match)) {
                $opfPath = $match[1];
            }

            if (! $opfPath) {
                return 1;
            }

            $opf = $zip->getFromName($opfPath);
            if ($opf === false) {
                return 1;
            }

            if (preg_match_all('/<itemref\b([^>]*)>/i', $opf, $spineMatches)) {
                $count = 0;
                foreach ($spineMatches[1] as $attrs) {
                    if (preg_match('/\blinear=["\']no["\']/i', $attrs)) {
                        continue;
                    }
                    $count++;
                }

                return max(1, $count);
            }

            return 1;
        } finally {
            $zip->close();
        }
    }
}

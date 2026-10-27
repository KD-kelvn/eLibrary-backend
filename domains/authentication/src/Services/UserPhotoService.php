<?php

namespace Modules\Authentication\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Authentication\Exceptions\AuthenticationException;
use Modules\Authentication\Models\User;
use Modules\Settings\Services\SettingsService;
use Spatie\Image\Enums\ImageDriver;
use Spatie\Image\Image;
use Throwable;

/**
 * FR-USR-02: validate, resize (512 + 128), strip EXIF, store privately.
 */
class UserPhotoService
{
    private const DISK = 'local';

    private const SIZE_MAIN = 512;

    private const SIZE_THUMB = 128;

    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    /**
     * @return array{photo_path: string, photo_thumb_path: string}
     */
    public function storeForUser(User $user, UploadedFile $upload): array
    {
        $this->assertAcceptedUpload($upload);

        $directory = 'user-photos/'.$user->id;
        $token = Str::lower(Str::random(12));
        $extension = $this->extensionFor($upload);

        $mainRelative = "{$directory}/{$token}-".self::SIZE_MAIN.".{$extension}";
        $thumbRelative = "{$directory}/{$token}-".self::SIZE_THUMB.".{$extension}";

        $disk = Storage::disk(self::DISK);
        $disk->makeDirectory($directory);

        $sourcePath = $upload->getRealPath() ?: $upload->getPathname();
        if (! is_string($sourcePath) || $sourcePath === '' || ! is_file($sourcePath)) {
            throw AuthenticationException::unprocessable('Could not read the uploaded photo.');
        }

        try {
            $this->resizeAndSave($sourcePath, $disk->path($mainRelative), self::SIZE_MAIN);
            $this->resizeAndSave($sourcePath, $disk->path($thumbRelative), self::SIZE_THUMB);
        } catch (Throwable $exception) {
            $disk->delete([$mainRelative, $thumbRelative]);

            throw AuthenticationException::unprocessable(
                'Photo processing failed: '.$exception->getMessage(),
            );
        }

        $this->assertNoExif($disk->path($mainRelative));
        $this->assertNoExif($disk->path($thumbRelative));

        $this->deleteStoredPaths(
            $user->profile?->photo_path,
            $user->profile?->photo_thumb_path,
        );

        return [
            'photo_path' => $mainRelative,
            'photo_thumb_path' => $thumbRelative,
            'profile_picture' => $mainRelative,
        ];
    }

    public function deleteStoredPaths(?string ...$paths): void
    {
        $disk = Storage::disk(self::DISK);

        foreach ($paths as $path) {
            if (is_string($path) && $path !== '' && $disk->exists($path)) {
                $disk->delete($path);
            }
        }
    }

    protected function assertAcceptedUpload(UploadedFile $upload): void
    {
        if (! $upload->isValid()) {
            throw AuthenticationException::unprocessable($upload->getErrorMessage() ?: 'Invalid photo upload.');
        }

        $allowed = collect($this->settings->get('files.image_types') ?? ['jpeg', 'png', 'webp'])
            ->map(fn ($type) => strtolower((string) $type))
            ->flatMap(fn (string $type) => match ($type) {
                'jpeg', 'jpg' => ['jpeg', 'jpg', 'image/jpeg'],
                'png' => ['png', 'image/png'],
                'webp' => ['webp', 'image/webp'],
                default => [$type],
            })
            ->unique()
            ->values()
            ->all();

        $mime = strtolower((string) ($upload->getMimeType() ?: $upload->getClientMimeType()));
        $ext = strtolower((string) ($upload->getClientOriginalExtension() ?: $upload->extension() ?: ''));

        if (! in_array($mime, $allowed, true) && ! in_array($ext, $allowed, true)) {
            throw AuthenticationException::unprocessable(
                'Photo must be JPEG, PNG or WebP (see files.image_types).',
            );
        }

        $maxMb = (int) ($this->settings->get('files.image_max_mb') ?? 2);
        $maxBytes = max(1, $maxMb) * 1024 * 1024;
        $size = (int) ($upload->getSize() ?? 0);

        if ($size <= 0 || $size > $maxBytes) {
            throw AuthenticationException::unprocessable(
                "Photo must be at most {$maxMb} MB.",
            );
        }
    }

    protected function resizeAndSave(string $sourcePath, string $destinationPath, int $maxEdge): void
    {
        Image::useImageDriver(ImageDriver::Gd)
            ->loadFile($sourcePath)
            ->width($maxEdge)
            ->save($destinationPath);
    }

    protected function assertNoExif(string $absolutePath): void
    {
        if (! function_exists('exif_read_data') || ! is_file($absolutePath)) {
            return;
        }

        $mime = mime_content_type($absolutePath) ?: '';
        if (! str_contains($mime, 'jpeg') && ! str_contains($mime, 'jpg')) {
            return;
        }

        $exif = @exif_read_data($absolutePath);
        if (is_array($exif) && $this->exifHasPayload($exif)) {
            throw AuthenticationException::unprocessable('Photo EXIF data was not stripped.');
        }
    }

    /**
     * @param  array<string, mixed>  $exif
     */
    protected function exifHasPayload(array $exif): bool
    {
        foreach (['Make', 'Model', 'DateTimeOriginal', 'GPSLatitude', 'Artist', 'ImageDescription'] as $key) {
            if (! empty($exif[$key])) {
                return true;
            }
        }

        return false;
    }

    protected function extensionFor(UploadedFile $upload): string
    {
        $mime = strtolower((string) ($upload->getMimeType() ?: ''));

        return match (true) {
            str_contains($mime, 'png') => 'png',
            str_contains($mime, 'webp') => 'webp',
            default => 'jpg',
        };
    }
}

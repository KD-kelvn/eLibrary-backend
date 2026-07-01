<?php

namespace App\Repositories;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class FilesRepository
{
    public function __construct(
        protected FilesBase64Repository $filesBase64Repository
    ) {}

    /**
     * Save a file uploaded from an HTTP request.
     *
     * @return array{file_name: string, file_path: string, file_type: string, file_size: string}
     */
    public function saveFromUpload(
        UploadedFile $file,
        string $directory,
        ?string $fileName = null,
        string $disk = 'public',
    ): array {
        $this->assertValidUpload($file);

        $storedFileName = $this->buildStoredFileName($file, $fileName);
        $directory = trim($directory, '/');

        $storedPath = $file->storeAs($directory, $storedFileName, $disk);

        if ($storedPath === false) {
            throw new RuntimeException('Failed to save uploaded file to storage.');
        }

        return $this->buildFileMeta(
            displayName: $fileName ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            storedPath: $storedPath,
            mimeType: $file->getMimeType() ?? $file->getClientMimeType() ?? 'application/octet-stream',
            size: $file->getSize() ?? Storage::disk($disk)->size($storedPath),
        );
    }

    /**
     * Save multiple uploaded files from a request.
     *
     * @param  array<int, UploadedFile>  $files
     * @return array<int, array{file_name: string, file_path: string, file_type: string, file_size: string}>
     */
    public function saveManyFromUpload(
        array $files,
        string $directory,
        string $disk = 'public',
    ): array {
        return array_map(
            fn (UploadedFile $file) => $this->saveFromUpload($file, $directory, disk: $disk),
            $files,
        );
    }

    /**
     * Save a single request file input (handles null safely).
     *
     * @return array{file_name: string, file_path: string, file_type: string, file_size: string}|null
     */
    public function saveFromRequest(
        ?UploadedFile $file,
        string $directory,
        ?string $fileName = null,
        string $disk = 'public',
    ): ?array {
        if ($file === null) {
            return null;
        }

        return $this->saveFromUpload($file, $directory, $fileName, $disk);
    }

    /**
     * @param  array<int, UploadedFile>|UploadedFile|null  $files
     * @return array<int, array{file_name: string, file_path: string, file_type: string, file_size: string}>|array{file_name: string, file_path: string, file_type: string, file_size: string}|null
     */
    public function saveRequestFiles(
        array|UploadedFile|null $files,
        string $directory,
        ?string $fileName = null,
        string $disk = 'public',
    ): array|null {
        if ($files === null) {
            return null;
        }

        if ($files instanceof UploadedFile) {
            return $this->saveFromUpload($files, $directory, $fileName, $disk);
        }

        return $this->saveManyFromUpload($files, $directory, $disk);
    }

    public function delete(string $filePath, string $disk = 'public'): bool
    {
        return Storage::disk($disk)->delete($filePath);
    }

    public function existsOnDisk(string $filePath, string $disk = 'public'): bool
    {
        return Storage::disk($disk)->exists($filePath);
    }

    public function encodeToBase64(string $filePath): array
    {
        return $this->filesBase64Repository->encodeToBase64($filePath);
    }

    public function getAbsolutePath(string $filePath): string
    {
        return $this->filesBase64Repository->getFilePath($filePath);
    }

    public function exists(string $path): bool
    {
        return $this->filesBase64Repository->checkFileExists($path);
    }

    public function getMimeType(string $filePath): string|false
    {
        return $this->filesBase64Repository->getMimeType($filePath);
    }

    public function saveBase64ToStorage(string $base64, string $path, string $fileName): array
    {
        return $this->filesBase64Repository->saveToStorage($base64, $path, $fileName);
    }

    protected function assertValidUpload(UploadedFile $file): void
    {
        if (! $file->isValid()) {
            throw new InvalidArgumentException($file->getErrorMessage() ?: 'Invalid uploaded file.');
        }
    }

    protected function buildStoredFileName(UploadedFile $file, ?string $fileName = null): string
    {
        $baseName = $fileName ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $baseName = Str::slug($baseName) ?: 'file';

        $extension = $file->getClientOriginalExtension()
            ?: $file->extension()
            ?: $file->guessExtension()
            ?: 'bin';

        return sprintf(
            '%s_%s.%s',
            $baseName,
            now()->format('ymdHis'),
            strtolower($extension),
        );
    }

    /**
     * @return array{file_name: string, file_path: string, file_type: string, file_size: string}
     */
    protected function buildFileMeta(
        string $displayName,
        string $storedPath,
        string $mimeType,
        int $size,
    ): array {
        return [
            'file_name' => $displayName,
            'file_path' => $storedPath,
            'file_type' => $mimeType,
            'file_size' => (string) $size,
        ];
    }
}

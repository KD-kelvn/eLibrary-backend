<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Storage;

class FilesBase64Repository
{
    public function saveToStorage($base64, $path, $fileName)
    {
        $dataType = explode(';', $base64)[0];
        $fileString = explode(',', $base64)[1];

        $file = base64_decode($fileString);
        $filePath = "{$path}/{$fileName}_".now()->format('DHi').'.pdf';
        $path = Storage::disk('public')->put($filePath, $file);
        if (! $path) {
            throw new \Exception('Failed to save file to storage');
        }
        $size = (string) strlen($file);

        return [
            'file_name' => $fileName,
            'file_path' => $filePath,
            'file_type' => $dataType,
            'file_size' => $size,
        ];
    }

    public function encodeToBase64($filePath)
    {
        $file = file_get_contents($this->getFilePath($filePath));
        $base64 = base64_encode($file);
        $mimeType = $this->getMimeType($this->getFilePath($filePath));

        return [
            'base64' => 'data:'.$mimeType.';base64,'.$base64,
            'mimeType' => $mimeType,
        ];
    }

    public function getFilePath(string $filePath)
    {
        return storage_path("app/public/$filePath");
    }

    public function checkFileExists(string $path)
    {
        return file_exists($path);
    }

    public function getMimeType(string $filePath)
    {
        return mime_content_type($filePath);
    }
}

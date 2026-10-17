<?php

namespace Modules\BookCatalogue\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Modules\BookCatalogue\Models\DigitalBook;
use RuntimeException;

class SecureDigitalBookStorageCommand extends Command
{
    protected $signature = 'book-catalogue:secure-digital-storage';

    protected $description = 'Move digital book files from public storage to private storage';

    public function handle(): int
    {
        $public = Storage::disk('public');
        $private = Storage::disk('ebooks');
        $moved = 0;
        $missing = 0;

        DigitalBook::query()->eachById(function (DigitalBook $book) use ($public, $private, &$moved, &$missing): void {
            $path = $book->file_path;

            if ($private->exists($path)) {
                $this->assertChecksum($book, $private->path($path));
                if ($public->exists($path) && ! $public->delete($path)) {
                    throw new RuntimeException("Unable to remove public ebook file [{$path}].");
                }

                return;
            }

            if (! $public->exists($path)) {
                $this->error("Missing ebook file [{$path}] for digital book {$book->id}.");
                $missing++;

                return;
            }

            $private->makeDirectory(dirname($path));

            $stream = $public->readStream($path);
            if ($stream === false) {
                throw new RuntimeException("Unable to read public ebook file [{$path}].");
            }

            try {
                $copied = $private->writeStream($path, $stream);
            } finally {
                fclose($stream);
            }

            if (! $copied || ! $private->exists($path)) {
                throw new RuntimeException("Unable to copy ebook file [{$path}] to private storage.");
            }

            $this->assertChecksum($book, $private->path($path));

            if (! $public->delete($path)) {
                throw new RuntimeException("Unable to remove public ebook file [{$path}].");
            }

            $moved++;
        });

        $this->info("Secured {$moved} ebook file(s).");

        return $missing === 0 ? self::SUCCESS : self::FAILURE;
    }

    protected function assertChecksum(DigitalBook $book, string $path): void
    {
        if (! $book->checksum) {
            return;
        }

        $checksum = hash_file('sha256', $path);
        if ($checksum === false || ! hash_equals($book->checksum, $checksum)) {
            throw new RuntimeException("Checksum mismatch for digital book {$book->id}.");
        }
    }
}

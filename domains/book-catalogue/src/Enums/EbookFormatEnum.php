<?php

namespace Modules\BookCatalogue\Enums;

enum EbookFormatEnum: string
{
    case Epub = 'epub';
    case Pdf = 'pdf';
    case Mobi = 'mobi';

    public function label(): string
    {
        return match ($this) {
            self::Epub => 'EPUB',
            self::Pdf => 'PDF',
            self::Mobi => 'MOBI',
        };
    }

    public function mimeType(): string
    {
        return match ($this) {
            self::Epub => 'application/epub+zip',
            self::Pdf => 'application/pdf',
            self::Mobi => 'application/x-mobipocket-ebook',
        };
    }
}

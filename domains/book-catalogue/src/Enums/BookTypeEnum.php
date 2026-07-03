<?php

namespace Modules\BookCatalogue\Enums;

enum BookTypeEnum: string
{
    case Physical = 'PHYSICAL';
    case Digital = 'DIGITAL';

    public function label(): string
    {
        return match ($this) {
            self::Physical => 'Physical',
            self::Digital => 'Digital',
        };
    }
}

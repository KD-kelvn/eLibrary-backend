<?php

namespace Modules\Authorization\Enums;

enum RoleStatusEnum: string
{
    case Active = 'active';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Inactive => 'Inactive',
        };
    }

    public function value(): string
    {
        return match ($this) {
            self::Active => 'active',
            self::Inactive => 'inactive',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'green',
            self::Inactive => 'red',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Active => 'check',
            self::Inactive => 'x',
        };
    }
}

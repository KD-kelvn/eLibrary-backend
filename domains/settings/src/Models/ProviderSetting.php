<?php

namespace Modules\Settings\Models;

use Illuminate\Database\Eloquent\Model;

class ProviderSetting extends Model
{
    protected $table = 'provider_settings';

    protected $fillable = [
        'group',
        'config',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'config' => 'encrypted:array',
        ];
    }
}

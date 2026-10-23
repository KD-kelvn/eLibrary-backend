<?php

namespace Modules\Settings\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * Append-only library setting row (SDD §12.1).
 * Only `cancelled_at` may be updated after insert.
 */
class SettingValue extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'key',
        'scope',
        'value',
        'effective_from',
        'created_by',
        'reason',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'array',
            'effective_from' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
        ];
    }

    public function getTable(): string
    {
        return Schema::getConnection()->getDriverName() === 'pgsql'
            ? 'settings.setting_values'
            : 'setting_values';
    }

    /**
     * Stored JSON is always `{"v": <scalar|array>}` so jsonb stays consistent.
     */
    public function decodedValue(): mixed
    {
        $payload = $this->value;

        if (is_array($payload) && array_key_exists('v', $payload)) {
            return $payload['v'];
        }

        return $payload;
    }
}

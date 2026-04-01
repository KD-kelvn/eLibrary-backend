<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use Yajra\Auditable\AuditableWithDeletesTrait;

class BaseModelWithAudits extends Model implements Auditable
{
    use AuditableWithDeletesTrait, \OwenIt\Auditing\Auditable, SoftDeletes;

    public function creater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by', 'id');
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    // Property formatting traits
    protected function formatToLowerCase($value): string
    {
        return strtolower(preg_replace('/\s+/', '', $value));
    }

    protected function formatToCamelCase($value): string
    {
        $value = str_replace('_', ' ', $value);

        return str_replace(' ', '', ucwords($value));
    }
}

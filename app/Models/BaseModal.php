<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Authentication\Models\User;
use Yajra\Auditable\AuditableWithDeletesTrait;

class BaseModal extends Model
{
    use AuditableWithDeletesTrait, HasFactory, SoftDeletes;

    public function creator(): BelongsTo
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
        return strtolower(trim($value));
    }

    protected function formatToCamelCase($value): string
    {
        $value = str_replace('_', ' ', $value);

        return ucwords($value);
    }
}

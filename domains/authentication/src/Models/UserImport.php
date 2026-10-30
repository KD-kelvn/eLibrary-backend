<?php

namespace Modules\Authentication\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class UserImport extends Model
{
    protected $fillable = [
        'type',
        'status',
        'original_filename',
        'mime',
        'storage_path',
        'uploaded_by',
        'committed_at',
        'summary',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'summary' => 'array',
            'committed_at' => 'datetime',
        ];
    }

    public function getTable(): string
    {
        return Schema::getConnection()->getDriverName() === 'pgsql'
            ? 'auth.user_imports'
            : 'user_imports';
    }

    public function rows(): HasMany
    {
        return $this->hasMany(UserImportRow::class, 'user_import_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isCommitted(): bool
    {
        return $this->status === 'committed';
    }
}

<?php

namespace Modules\Authentication\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class UserImportRow extends Model
{
    protected $fillable = [
        'user_import_id',
        'row_number',
        'payload',
        'status',
        'errors',
        'matched_user_id',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'errors' => 'array',
            'row_number' => 'integer',
        ];
    }

    public function getTable(): string
    {
        return Schema::getConnection()->getDriverName() === 'pgsql'
            ? 'auth.user_import_rows'
            : 'user_import_rows';
    }

    public function import(): BelongsTo
    {
        return $this->belongsTo(UserImport::class, 'user_import_id');
    }

    public function matchedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'matched_user_id');
    }
}

<?php

namespace Modules\Academic\Models;

use App\Models\BaseModelWithAudits;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class Department extends BaseModelWithAudits
{
    protected $fillable = [
        'faculty_id',
        'code',
        'name_en',
        'name_sw',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function getTable(): string
    {
        return Schema::getConnection()->getDriverName() === 'pgsql'
            ? 'academic.departments'
            : 'departments';
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class, 'faculty_id', 'id');
    }

    public function programmes(): HasMany
    {
        return $this->hasMany(Programme::class, 'department_id', 'id');
    }
}

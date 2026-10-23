<?php

namespace Modules\Academic\Models;

use App\Models\BaseModelWithAudits;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class Faculty extends BaseModelWithAudits
{
    protected $fillable = [
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
            ? 'academic.faculties'
            : 'faculties';
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class, 'faculty_id', 'id');
    }
}

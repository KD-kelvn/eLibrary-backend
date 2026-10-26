<?php

namespace Modules\Academic\Models;

use App\Models\BaseModelWithAudits;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class Programme extends BaseModelWithAudits
{
    protected $fillable = [
        'department_id',
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
            ? 'academic.programmes'
            : 'programmes';
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id', 'id');
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, 'programme_id', 'id');
    }
}

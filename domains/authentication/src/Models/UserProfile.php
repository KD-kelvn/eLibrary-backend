<?php

namespace Modules\Authentication\Models;

use App\Models\BaseModelWithAudits;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;
use Modules\Academic\Models\Department;
use Modules\Academic\Models\Faculty;
use Modules\Academic\Models\Programme;

class UserProfile extends BaseModelWithAudits
{
    protected $fillable = [
        'user_id',
        'fullname',
        'first_name',
        'middle_name',
        'last_name',
        'gender',
        'dob',
        'profile_picture',
        'faculty_id',
        'department_id',
        'programme_id',
        'year_of_study',
        'photo_path',
        'photo_thumb_path',
        'source',
        'core_synced_at',
    ];

    /**
     * Display / search name derived from parts when present.
     */
    public function composedFullname(): string
    {
        $parts = array_filter([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
        ], fn ($part) => filled($part));

        if ($parts !== []) {
            return implode(' ', $parts);
        }

        return (string) ($this->fullname ?? '');
    }

    protected function casts(): array
    {
        return [
            'dob' => 'date',
            'year_of_study' => 'integer',
            'core_synced_at' => 'datetime',
        ];
    }

    public function getTable(): string
    {
        return Schema::getConnection()->getDriverName() === 'pgsql'
            ? 'auth.user_profiles'
            : 'user_profiles';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class, 'faculty_id', 'id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id', 'id');
    }

    public function programme(): BelongsTo
    {
        return $this->belongsTo(Programme::class, 'programme_id', 'id');
    }
}

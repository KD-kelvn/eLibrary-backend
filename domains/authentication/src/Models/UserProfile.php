<?php

namespace Modules\Authentication\Models;

use App\Models\BaseModelWithAudits;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends BaseModelWithAudits
{
    protected $fillable = [
        'user_id',
        'fullname',
        'gender',
        'dob',
        'profile_picture',
    ];

    protected function casts(): array
    {
        return [
            'dob' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}

<?php

namespace Modules\Authorization\Models;

use App\Models\BaseModelWithAudits;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Authentication\Models\User;

class UserSystemAction extends BaseModelWithAudits
{
    protected $table = 'auth.user_system_actions';

    protected $fillable = [
        'user_id',
        'system_action_id',
        'user_role_id',
        'created_by',
        'updated_by',
        'deleted_by',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function systemAction(): BelongsTo
    {
        return $this->belongsTo(SystemAction::class, 'system_action_id');
    }

    public function userRole(): BelongsTo
    {
        return $this->belongsTo(UserRole::class, 'user_role_id');
    }
}

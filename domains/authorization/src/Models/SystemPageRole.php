<?php

namespace Modules\Authorization\Models;

use App\Models\BaseModal;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemPageRole extends BaseModal
{
    protected $table = 'auth.system_page_roles';

    protected $fillable = [
        'system_page_id',
        'role_id',
    ];

    public function systemPage(): BelongsTo
    {
        return $this->belongsTo(SystemPage::class, 'system_page_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }
}

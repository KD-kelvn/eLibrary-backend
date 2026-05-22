<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use Yajra\Auditable\AuditableWithDeletesTrait;

class BaseModelWithAudits extends Model implements Auditable
{
    use AuditableWithDeletesTrait, \OwenIt\Auditing\Auditable, SoftDeletes;
}

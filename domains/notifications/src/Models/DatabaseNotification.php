<?php

namespace Modules\Notifications\Models;

use Illuminate\Notifications\DatabaseNotification as BaseDatabaseNotification;
use Illuminate\Support\Facades\Schema;

class DatabaseNotification extends BaseDatabaseNotification
{
    public function getTable(): string
    {
        return Schema::getConnection()->getDriverName() === 'pgsql'
            ? 'notify.notifications'
            : 'notify_notifications';
    }
}

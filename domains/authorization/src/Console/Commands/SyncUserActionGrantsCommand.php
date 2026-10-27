<?php

namespace Modules\Authorization\Console\Commands;

use Illuminate\Console\Command;
use Modules\Authorization\Services\AccessManagement\RoleActionGrantService;

class SyncUserActionGrantsCommand extends Command
{
    protected $signature = 'authorization:sync-action-grants';

    protected $description = 'Re-grant role-default system actions for all active role assignments (V1-004)';

    public function handle(RoleActionGrantService $grants): int
    {
        $count = $grants->syncAllActive();
        $this->info("Synced action grants for {$count} active role assignment(s).");

        return self::SUCCESS;
    }
}

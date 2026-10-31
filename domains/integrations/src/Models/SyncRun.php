<?php

namespace Modules\Integrations\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;
use Modules\Authentication\Models\User;

class SyncRun extends Model
{
    public const MODE_PUSH = 'push';

    public const MODE_PULL = 'pull';

    public const STATUS_QUEUED = 'queued';

    public const STATUS_RUNNING = 'running';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'mode',
        'status',
        'idempotency_key',
        'cursor',
        'created_count',
        'updated_count',
        'deactivated_count',
        'unchanged_count',
        'failed_count',
        'triggered_by',
        'error_summary',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function getTable(): string
    {
        return Schema::getConnection()->getDriverName() === 'pgsql'
            ? 'integration.sync_runs'
            : 'sync_runs';
    }

    public function items(): HasMany
    {
        return $this->hasMany(SyncRunItem::class, 'sync_run_id');
    }

    public function triggeredBy()
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }
}

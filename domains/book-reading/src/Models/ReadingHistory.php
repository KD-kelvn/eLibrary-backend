<?php

namespace Modules\BookReading\Models;

use App\Models\BaseModelWithAudits;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Authentication\Models\User;
use Modules\BookCatalogue\Models\BookDetail;

class ReadingHistory extends BaseModelWithAudits
{
    protected $table = 'book_catalog.reading_histories';

    protected $fillable = [
        'user_id',
        'book_detail_id',
        'progress_percent',
        'current_location',
        'duration_seconds',
        'started_at',
        'last_read_at',
        'completed_at',
        'device',
    ];

    protected function casts(): array
    {
        return [
            'progress_percent' => 'decimal:2',
            'duration_seconds' => 'integer',
            'started_at' => 'datetime',
            'last_read_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function bookDetail(): BelongsTo
    {
        return $this->belongsTo(BookDetail::class, 'book_detail_id');
    }
}

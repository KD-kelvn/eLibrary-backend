<?php

namespace Modules\BookCatalogue\Models;

use App\Models\BaseModelWithAudits;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tag extends BaseModelWithAudits
{
    protected $table = 'book_catalog.tags';

    protected $fillable = [
        'name',
        'description',
        'code',
    ];

    public function bookHasTags(): HasMany
    {
        return $this->hasMany(BookHasTag::class, 'tag_id', 'id');
    }

    public function bookDetails(): BelongsToMany
    {
        return $this->belongsToMany(
            BookDetail::class,
            BookHasTag::class,
            'tag_id',
            'book_detail_id',
        )->withPivot('id');
    }
}

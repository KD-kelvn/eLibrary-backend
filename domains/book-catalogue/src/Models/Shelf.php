<?php

namespace Modules\BookCatalogue\Models;

use App\Models\BaseModelWithAudits;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shelf extends BaseModelWithAudits
{
    protected $table = 'book_catalog.shelves';

    protected $fillable = [
        'name',
        'description',
        'code',
        'location',
        'number',
        'rack',
    ];

    public function physicalBooks(): HasMany
    {
        return $this->hasMany(PhysicalBook::class, 'shelf_id', 'id');
    }
}

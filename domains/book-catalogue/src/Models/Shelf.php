<?php

namespace Modules\BookCatalogue\Models;

use App\Models\BaseModelWithAudits;
use App\Support\QualifiedTable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shelf extends BaseModelWithAudits
{
    protected $fillable = [
        'name',
        'description',
        'code',
        'location',
        'number',
        'rack',
    ];

    public function getTable(): string
    {
        return QualifiedTable::name('book_catalog', 'shelves');
    }

    public function physicalBooks(): HasMany
    {
        return $this->hasMany(PhysicalBook::class, 'shelf_id', 'id');
    }
}

<?php

namespace Modules\BookCatalogue\Services\Reusables;

use Illuminate\Support\Collection;
use Modules\BookCatalogue\Models\Category;
use Modules\BookCatalogue\Models\Shelf;
use Modules\BookCatalogue\Models\SubCategory;
use Modules\BookCatalogue\Models\Tag;

class ReusableOptionsService
{
    /**
     * Fetch all reusable lookups grouped by type for use in
     * book create forms and catalogue filters.
     *
     * @return array<string, Collection>
     */
    public function all(): array
    {
        return [
            'categories' => Category::query()->orderBy('name')->get(['id', 'name', 'code']),
            'subCategories' => SubCategory::query()->orderBy('name')->get(['id', 'name', 'code']),
            'tags' => Tag::query()->orderBy('name')->get(['id', 'name', 'code']),
            'shelves' => Shelf::query()->orderBy('name')->get(['id', 'name', 'code']),
        ];
    }
}

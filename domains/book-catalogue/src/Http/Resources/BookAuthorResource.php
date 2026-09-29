<?php

namespace Modules\BookCatalogue\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;
use Modules\BookCatalogue\Models\BookDetail;

/**
 * Author aggregate row (grouped `book_details.authors`).
 *
 * @mixin BookDetail
 */
class BookAuthorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->authors,
            'booksCount' => (int) $this->books_count,
            'latestPubYear' => $this->latest_pub_year ? (int) $this->latest_pub_year : null,
        ];
    }
}

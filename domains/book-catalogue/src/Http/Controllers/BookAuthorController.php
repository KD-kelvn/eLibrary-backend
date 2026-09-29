<?php

namespace Modules\BookCatalogue\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\BookCatalogue\Http\Requests\IndexBookAuthorRequest;
use Modules\BookCatalogue\Http\Resources\BookAuthorResource;
use Modules\BookCatalogue\Services\BookAuthorService;
use Modules\BookCatalogue\Traits\HandlesApiResponses;

class BookAuthorController extends Controller
{
    use HandlesApiResponses;

    public function __construct(
        private readonly BookAuthorService $authors
    ) {}

    public function index(IndexBookAuthorRequest $request): JsonResponse
    {
        try {
            return $this->successResponse(
                BookAuthorResource::collection($this->authors->list($request->validated())),
                'Authors retrieved.',
            );
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }
}

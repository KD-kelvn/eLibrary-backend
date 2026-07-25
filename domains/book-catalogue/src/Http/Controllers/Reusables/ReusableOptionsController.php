<?php

namespace Modules\BookCatalogue\Http\Controllers\Reusables;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\BookCatalogue\Http\Resources\ReusableOptionResource;
use Modules\BookCatalogue\Services\Reusables\ReusableOptionsService;
use Modules\BookCatalogue\Traits\HandlesApiResponses;

class ReusableOptionsController extends Controller
{
    use HandlesApiResponses;

    public function __construct(private readonly ReusableOptionsService $reusableOptionsService) {}

    public function index(): JsonResponse
    {
        try {
            $groups = $this->reusableOptionsService->all();

            return $this->successResponse([
                'categories' => ReusableOptionResource::collection($groups['categories']),
                'subCategories' => ReusableOptionResource::collection($groups['subCategories']),
                'tags' => ReusableOptionResource::collection($groups['tags']),
                'shelves' => ReusableOptionResource::collection($groups['shelves']),
            ], 'Reusable options retrieved.');
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }
}

<?php

namespace Modules\BookBorrowing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\BookBorrowing\Models\BorrowingRequest;
use Modules\BookBorrowing\Services\LibrarianDashboardService;
use Modules\BookBorrowing\Traits\HandlesApiResponses;

class LibrarianDashboardController extends Controller
{
    use HandlesApiResponses;

    public function __construct(private readonly LibrarianDashboardService $service) {}

    public function __invoke(): JsonResponse
    {
        try {
            $this->authorize('viewAny', BorrowingRequest::class);

            return $this->successResponse(
                $this->service->summary(),
                'Librarian dashboard summary retrieved.',
            );
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }
}

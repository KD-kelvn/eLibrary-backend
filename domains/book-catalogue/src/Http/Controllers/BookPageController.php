<?php

namespace Modules\BookCatalogue\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Modules\BookCatalogue\Exceptions\BookCatalogueException;
use Modules\BookCatalogue\Models\BookDetail;
use Modules\BookCatalogue\Services\BookPageService;
use Modules\BookCatalogue\Traits\HandlesApiResponses;

class BookPageController extends Controller
{
    use HandlesApiResponses;

    public function __construct(private readonly BookPageService $bookPageService) {}

    public function show(string $book, string $page): JsonResponse
    {
        try {
            $bookDetail = BookDetail::query()->with('digitalBook')->findOrFail($book);
            $this->authorize('view', $bookDetail);

            $payload = $this->bookPageService->getPage(
                $bookDetail,
                (int) $page,
                (int) request()->user()->id,
            );

            return $this->successResponse($payload, 'Page retrieved.');
        } catch (BookCatalogueException $exception) {
            return $this->handleBookCatalogueException($exception);
        } catch (AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function start(string $book): JsonResponse
    {
        try {
            $bookDetail = BookDetail::query()->with('digitalBook')->findOrFail($book);
            $this->authorize('view', $bookDetail);

            $payload = $this->bookPageService->openForReading(
                $bookDetail,
                (int) request()->user()->id,
            );

            return $this->successResponse($payload, 'Reading started.');
        } catch (BookCatalogueException $exception) {
            return $this->handleBookCatalogueException($exception);
        } catch (AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }
}

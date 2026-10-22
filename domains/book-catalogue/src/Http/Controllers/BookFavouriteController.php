<?php

namespace Modules\BookCatalogue\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Modules\BookCatalogue\Exceptions\BookCatalogueException;
use Modules\BookCatalogue\Http\Requests\StoreBookFavouriteRequest;
use Modules\BookCatalogue\Http\Resources\MemberBookCardResource;
use Modules\BookCatalogue\Models\BookFavourite;
use Modules\BookCatalogue\Services\BookFavouriteService;
use Modules\BookCatalogue\Traits\HandlesApiResponses;

class BookFavouriteController extends Controller
{
    use HandlesApiResponses;

    public function __construct(private readonly BookFavouriteService $favourites) {}

    public function index(): JsonResponse
    {
        try {
            $this->authorize('viewAny', BookFavourite::class);

            $books = $this->favourites->listForUser((int) request()->user()->id);

            return $this->successResponse(
                MemberBookCardResource::collection($books),
                'Favourites retrieved.',
            );
        } catch (AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function store(StoreBookFavouriteRequest $request): JsonResponse
    {
        try {
            $favourite = $this->favourites->store(
                (int) $request->user()->id,
                (int) $request->validated('book_detail_id'),
            );

            $book = $favourite->bookDetail;
            $book?->setAttribute('is_favourite', true);

            return $this->resourceResponse(
                MemberBookCardResource::make($book),
                'Added to favourites.',
                201,
            );
        } catch (BookCatalogueException $exception) {
            return $this->handleBookCatalogueException($exception);
        } catch (AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function destroy(string $book): JsonResponse
    {
        try {
            $this->authorize('create', BookFavourite::class);

            $this->favourites->destroyForUserBook(
                (int) request()->user()->id,
                (int) $book,
            );

            return $this->successResponse(null, 'Removed from favourites.');
        } catch (BookCatalogueException $exception) {
            return $this->handleBookCatalogueException($exception);
        } catch (AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }
}

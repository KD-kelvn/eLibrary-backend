<?php

namespace Modules\BookCatalogue\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Modules\BookCatalogue\Enums\PhysicalBookCopyStatusEnum;
use Modules\BookCatalogue\Exceptions\BookCatalogueException;
use Modules\BookCatalogue\Http\Requests\UpdatePhysicalBookCopyStatusRequest;
use Modules\BookCatalogue\Http\Resources\PhysicalBookCopyResource;
use Modules\BookCatalogue\Models\PhysicalBookCopy;
use Modules\BookCatalogue\Services\PhysicalBookCopyService;
use Modules\BookCatalogue\Traits\HandlesApiResponses;

class PhysicalBookCopyController extends Controller
{
    use HandlesApiResponses;

    public function __construct(private readonly PhysicalBookCopyService $service) {}

    public function updateStatus(UpdatePhysicalBookCopyStatusRequest $request, string $physicalBookCopy): JsonResponse
    {
        try {
            $copy = PhysicalBookCopy::query()->find($physicalBookCopy);
            if (! $copy) {
                throw BookCatalogueException::notFound('Physical book copy');
            }

            $this->authorize('updateStatus', $copy);

            $updated = $this->service->updateStatus(
                (int) $physicalBookCopy,
                PhysicalBookCopyStatusEnum::from($request->validated('status')),
            );

            return $this->resourceResponse(
                PhysicalBookCopyResource::make($updated),
                'Physical book copy status updated.',
            );
        } catch (BookCatalogueException $exception) {
            return $this->handleBookCatalogueException($exception);
        } catch (AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }
}

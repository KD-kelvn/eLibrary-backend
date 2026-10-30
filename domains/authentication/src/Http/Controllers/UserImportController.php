<?php

namespace Modules\Authentication\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Authentication\Exceptions\AuthenticationException;
use Modules\Authentication\Http\Requests\CommitUserImportRequest;
use Modules\Authentication\Http\Requests\DownloadUserImportTemplateRequest;
use Modules\Authentication\Http\Requests\StoreUserImportRequest;
use Modules\Authentication\Services\UserImportService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserImportController extends Controller
{
    public function __construct(private readonly UserImportService $imports) {}

    public function template(DownloadUserImportTemplateRequest $request): StreamedResponse
    {
        $data = $request->validated();

        return $this->imports->downloadTemplate(
            $data['type'],
            $data['format'] ?? 'xlsx',
        );
    }

    public function store(StoreUserImportRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $result = $this->imports->preview(
                $request->file('file'),
                $data['type'],
                (int) $request->user()->id,
            );

            return $this->successResponse($result, 'Import preview ready.');
        } catch (AuthenticationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), $exception->getCode() ?: 422);
        }
    }

    public function commit(CommitUserImportRequest $request, string $userImport): JsonResponse
    {
        try {
            $result = $this->imports->commit((int) $userImport, (int) $request->user()->id);

            return $this->successResponse($result, 'Import committed.');
        } catch (AuthenticationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), $exception->getCode() ?: 422);
        }
    }

    public function errors(CommitUserImportRequest $request, string $userImport): StreamedResponse|JsonResponse
    {
        try {
            return $this->imports->downloadErrors((int) $userImport);
        } catch (AuthenticationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), $exception->getCode() ?: 422);
        }
    }
}

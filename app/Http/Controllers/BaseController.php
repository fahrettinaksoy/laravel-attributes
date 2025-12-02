<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\BaseCollection;
use App\Http\Resources\BaseResource;
use App\Services\BaseService;
use App\Services\Request\FormRequestService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

abstract class BaseController extends Controller
{
    public function __construct(
        protected readonly Model $model,
        protected readonly BaseService $service,
        protected readonly BaseResource $resource,
        protected readonly BaseCollection $collection,
        protected readonly FormRequestService $formRequestService,
        protected readonly array $requests,
    ) {}

    public function index(): JsonResponse
    {
        $formRequest = $this->formRequestService->resolve(
            request(),
            $this->requests['index']
        );

        $validatedData = $formRequest->validated();
        $filterData = $this->service->filter($validatedData);

        return $this->collection::make($filterData)
            ->response()
            ->setStatusCode(200);
    }

    public function show(): JsonResponse
    {
        $formRequest = $this->formRequestService->resolve(
            request(),
            $this->requests['show']
        );

        $validatedData = $formRequest->validated();
        $showData = $this->service->show($validatedData);

        return $this->resource::make($showData)
            ->response()
            ->setStatusCode(200);
    }

    public function store(): JsonResponse
    {
        $formRequest = $this->formRequestService->resolve(
            request(),
            $this->requests['store']
        );

        $validatedData = $formRequest->validated();

        $validatedData = $this->formRequestService->validateNestedData(
            $validatedData,
            'store',
            $this->model
        );

        $storeData = $this->service->store($validatedData);

        return $this->resource::make($storeData)
            ->response()
            ->setStatusCode(201);
    }

    public function update(): JsonResponse
    {
        $formRequest = $this->formRequestService->resolve(
            request(),
            $this->requests['update']
        );

        $validatedData = $formRequest->validated();

        $validatedData = $this->formRequestService->validateNestedData(
            $validatedData,
            'update',
            $this->model
        );

        $updatedData = $this->service->update($validatedData);

        return $this->resource::make($updatedData)
            ->response()
            ->setStatusCode(200);
    }

    public function destroy(Request $request): JsonResponse
    {
        $formRequest = $this->formRequestService->resolve(
            $request,
            $this->requests['destroy']
        );

        $validatedData = $formRequest->validated();

        $routeId = $request->route('id');
        $primaryKey = $this->model->getKeyName();

        if ($routeId !== null) {
            $deleteFilter = [$primaryKey => (int) $routeId];
        } else {
            $ids = is_array($validatedData['ids'])
                ? array_map('intval', $validatedData['ids'])
                : [(int) $validatedData['ids']];
            $deleteFilter = [$primaryKey => $ids];
        }

        $deletedCount = $this->service->destroy($deleteFilter);

        return response()->json([
            'message' => __('controllers/base.message.delete'),
            'deleted_count' => $deletedCount,
        ], 200);
    }
}

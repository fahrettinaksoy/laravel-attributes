<?php

declare(strict_types=1);

namespace App\Http\Controllers\Common;

use App\Http\Controllers\BaseController;
use App\Http\Requests\BaseDestroyRequest;
use App\Http\Requests\BaseIndexRequest;
use App\Http\Requests\BaseShowRequest;
use App\Http\Requests\BaseStoreRequest;
use App\Http\Requests\BaseUpdateRequest;
use App\Http\Resources\BaseCollection;
use App\Http\Resources\BaseResource;
use App\Services\BaseService;
use App\Services\PivotService;
use App\Services\Module\ModuleRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommonController extends BaseController
{
    protected PivotService $pivotService;

    public function __construct(
        BaseService $service,
        BaseResource $resource,
        BaseCollection $collection,
        PivotService $pivotService,
        ModuleRequestService $moduleRequestService,
    ) {
        $this->pivotService = $pivotService;

        parent::__construct(
            model: $service->getModel(),
            service: $service,
            resource: $resource,
            collection: $collection,
            moduleRequestService: $moduleRequestService,
            requests: [
                'index' => BaseIndexRequest::class,
                'show' => BaseShowRequest::class,
                'store' => BaseStoreRequest::class,
                'update' => BaseUpdateRequest::class,
                'destroy' => BaseDestroyRequest::class,
            ],
        );
    }

    public function pivotIndex(Request $request): JsonResponse
    {
        $formRequest = $this->moduleRequestService->resolveFormRequest(
            $request,
            $this->requests['index']
        );

        $validatedData = $formRequest->validated();

        $parentId = (int) $request->route('parent_id');
        $relationName = $request->route('relation');

        $filterData = $this->pivotService->filter($parentId, $relationName, $validatedData);

        return $this->collection::make($filterData)
            ->response()
            ->setStatusCode(200);
    }

    public function pivotShow(Request $request): JsonResponse
    {
        $formRequest = $this->moduleRequestService->resolveFormRequest(
            $request,
            $this->requests['show']
        );

        $validatedData = $formRequest->validated();

        $parentId = (int) $request->route('parent_id');
        $relationName = $request->route('relation');
        $relationId = (int) $request->route('relation_id');

        $showData = $this->pivotService->show($parentId, $relationName, $relationId, $validatedData);

        return $this->resource::make($showData)
            ->response()
            ->setStatusCode(200);
    }

    public function pivotStore(Request $request): JsonResponse
    {
        $formRequest = $this->moduleRequestService->resolveFormRequest(
            $request,
            $this->requests['store']
        );

        $validatedData = $formRequest->validated();

        $parentId = (int) $request->route('parent_id');
        $relationName = $request->route('relation');

        $storeData = $this->pivotService->store($parentId, $relationName, $validatedData);

        return $this->resource::make($storeData)
            ->response()
            ->setStatusCode(201);
    }

    public function pivotUpdate(Request $request): JsonResponse
    {
        $formRequest = $this->moduleRequestService->resolveFormRequest(
            $request,
            $this->requests['update']
        );

        $validatedData = $formRequest->validated();

        $parentId = (int) $request->route('parent_id');
        $relationName = $request->route('relation');
        $relationId = (int) $request->route('relation_id');

        $updatedData = $this->pivotService->update($parentId, $relationName, $relationId, $validatedData);

        return $this->resource::make($updatedData)
            ->response()
            ->setStatusCode(200);
    }

    public function pivotDestroy(Request $request): JsonResponse
    {
        $formRequest = $this->moduleRequestService->resolveFormRequest(
            $request,
            $this->requests['destroy']
        );

        $validatedData = $formRequest->validated();

        $parentId = (int) $request->route('parent_id');
        $relationName = $request->route('relation');
        $relationId = (int) $request->route('relation_id');

        $deletedCount = $this->pivotService->destroy($parentId, $relationName, $relationId, $validatedData);

        return response()->json([
            'message' => __('controllers/base.message.delete'),
            'updated_count' => $deletedCount,
        ], 200);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\BaseCollection;
use App\Http\Resources\BaseResource;
use App\Services\BaseService;
use App\Traits\FormRequestResolver;
use App\Traits\NestedValidationTrait;
use App\Traits\ResolvesFormRequests;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

abstract class BaseController extends Controller
{
    use FormRequestResolver;

    public function __construct(
        protected readonly Model $model,
        protected readonly BaseService $service,
        protected readonly BaseResource $resource,
        protected readonly BaseCollection $collection,
        protected readonly array $requests,
    ) {}

    public function index(): JsonResponse
    {
        $validatedData = $this->resolveFormRequest($this->requests['index'])->validated();
        $filterData = $this->service->filter($validatedData);

        return $this->collection::make($filterData)
            ->response()
            ->setStatusCode(200);
    }

    public function show(): JsonResponse
    {
        $validatedData = $this->resolveFormRequest($this->requests['show'])->validated();
        $showData = $this->service->show($validatedData);

        return $this->resource::make($showData)
            ->response()
            ->setStatusCode(200);
    }

    public function store(): JsonResponse
    {
        $validatedData = $this->resolveFormRequest($this->requests['store'])->validated();
        $validatedData = $this->validateNestedData($validatedData, 'store');
        $storeData = $this->service->store($validatedData);

        return $this->resource::make($storeData)
            ->response()
            ->setStatusCode(201);
    }

    public function update(): JsonResponse
    {
        $validatedData = $this->resolveFormRequest($this->requests['update'])->validated();
        $validatedData = $this->validateNestedData($validatedData, 'update');
        $updatedData = $this->service->update($validatedData);

        return $this->resource::make($updatedData)
            ->response()
            ->setStatusCode(200);
    }

    public function destroy(Request $request): JsonResponse
    {
        $validatedData = $this->resolveFormRequest($this->requests['destroy'])->validated();

        $routeId = $request->route('id');
        $primaryKey = $this->model->getKeyName();

        if ($routeId !== null) {
            $deleteFilter = [$primaryKey => (int) $routeId];
        } else {
            $ids = is_array($validatedData['ids']) ? array_map('intval', $validatedData['ids']) : [(int) $validatedData['ids']];
            $deleteFilter = [$primaryKey => $ids];
        }

        $deletedCount = $this->service->destroy($deleteFilter);

        return response()->json([
            'message' => __('controllers/base.message.delete'),
            'deleted_count' => $deletedCount,
        ], 200);
    }
}

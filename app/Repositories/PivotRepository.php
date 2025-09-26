<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\QueryBuilder;

class PivotRepository implements BaseRepositoryInterface
{
    protected Model $model;

    protected Model $parentModel;

    protected string $relationName;

    public function setModel(Model $model): void
    {
        $this->model = $model;
    }

    public function setParentModel(Model $parentModel): void
    {
        $this->parentModel = $parentModel;
    }

    public function setRelationName(string $relationName): void
    {
        $this->relationName = $relationName;
    }

    public function getModel(): Model
    {
        return $this->model;
    }

    public function all(): Collection
    {
        return $this->model->get();
    }

    public function filter(array $filteredData = []): LengthAwarePaginator
    {
        $relationQuery = $this->parentModel->{$this->relationName}();

        return QueryBuilder::for($relationQuery)
            ->allowedFields($this->model->allowedShowing ?? [])
            ->allowedFilters($this->model->allowedFiltering ?? [])
            ->allowedSorts($this->model->allowedSorting ?? [])
            ->allowedIncludes($this->model->allowedRelations ?? [])
            ->defaultSort($this->model->defaultSorting ?? 'id')
            ->with($this->model->defaultRelations ?? [])
            ->paginate($filteredData['limit'] ?? config('table.limit', 25))
            ->appends($filteredData);
    }

    public function show(array $filteredData = [])
    {
        $relationQuery = $this->parentModel->{$this->relationName}();

        return QueryBuilder::for($relationQuery)
            ->allowedFields($this->model->allowedShowing ?? [])
            ->allowedFilters($this->model->allowedFiltering ?? [])
            ->allowedIncludes($this->model->allowedRelations ?? [])
            ->with($this->model->defaultRelations ?? [])
            ->where($filteredData['filter'])
            ->first();
    }

    public function create(array $createdData = []): Model
    {
        return $this->parentModel->{$this->relationName}()->create($createdData);
    }

    public function update(array $updatedField = [], array $updatedData = []): Model
    {
        $relationQuery = $this->parentModel->{$this->relationName}();
        $relationQuery->where($updatedField)->update($updatedData);

        return $relationQuery->where($updatedField)->firstOrFail();
    }

    public function delete(array $deletedField = []): int
    {
        return $this->parentModel->{$this->relationName}()->where($deletedField)->delete();
    }
}

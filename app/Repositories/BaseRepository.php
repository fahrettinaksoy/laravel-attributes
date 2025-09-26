<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Traits\HasManyRelationDetector;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\QueryBuilder;

class BaseRepository implements BaseRepositoryInterface
{
    use HasManyRelationDetector;

    protected Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
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
        return QueryBuilder::for($this->model->newQuery())
            ->allowedFields($this->model->allowedShowing ?? [])
            ->allowedFilters(array_merge(
                $this->model->allowedFiltering ?? [],
                method_exists($this->model, 'getCustomFilters') ? $this->model->getCustomFilters() : [],
            ))
            ->allowedSorts($this->model->allowedSorting ?? [])
            ->allowedIncludes($this->model->allowedRelations ?? [])
            ->defaultSort($this->model->defaultSorting ?? 'id')
            ->with($this->model->defaultRelations ?? [])
            ->paginate($filteredData['limit'] ?? config('table.limit', 25))
            ->appends($filteredData);
    }

    public function show(array $filteredData = [])
    {
        return QueryBuilder::for($this->model->newQuery())
            ->allowedFields($this->model->allowedShowing ?? [])
            ->allowedFilters(array_merge(
                $this->model->allowedFiltering ?? [],
                method_exists($this->model, 'getCustomFilters') ? $this->model->getCustomFilters() : [],
            ))
            ->allowedIncludes($this->model->allowedRelations ?? [])
            ->with($this->model->defaultRelations ?? [])
            ->where($filteredData['filter'])
            ->first();
    }

    public function create(array $createdData = []): Model
    {
        $relations = $this->getHasManyRelationMethods($this->model);
        $nested = [];

        foreach ($relations as $relationName) {
            if (isset($createdData[$relationName]) && is_array($createdData[$relationName])) {
                $nested[$relationName] = $createdData[$relationName];
                unset($createdData[$relationName]);
            }
        }

        return DB::transaction(function () use ($createdData, $nested) {
            $parent = $this->model->create($createdData);

            foreach ($nested as $relationName => $records) {
                $relationObj = $parent->{$relationName}();
                $relationObj->createMany($records);
            }

            return $parent->load(array_keys($nested));
        });
    }

    public function update(array $updatedField = [], array $updatedData = []): Model
    {
        $relations = $this->getHasManyRelationMethods($this->model);
        $nested = [];

        foreach ($relations as $relationName) {
            if (isset($updatedData[$relationName]) && is_array($updatedData[$relationName])) {
                $nested[$relationName] = $updatedData[$relationName];
                unset($updatedData[$relationName]);
            }
        }

        return DB::transaction(function () use ($updatedField, $updatedData, $nested) {
            $this->model->where($updatedField)->update($updatedData);
            $parent = $this->model->where($updatedField)->firstOrFail();

            foreach ($nested as $relationName => $records) {
                $relationObj = $parent->{$relationName}();
                $relationObj->delete();
                $relationObj->createMany($records);
            }

            return $parent->load(array_keys($nested));
        });
    }

    public function delete(array $deletedField = []): int
    {
        $query = $this->model->newQuery();

        foreach ($deletedField as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }

        return $query->delete();
    }
}

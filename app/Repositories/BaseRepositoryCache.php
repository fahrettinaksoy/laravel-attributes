<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class BaseRepositoryCache implements BaseRepositoryInterface
{
    protected BaseRepositoryInterface $repository;

    protected int $cacheTTL = 3600;

    protected string $cacheTag;

    public function __construct(BaseRepositoryInterface $repository)
    {
        $this->repository = $repository;
        $this->cacheTTL = (int) config('cache.seconds', 3600);
        $this->cacheTag = $this->getModel()->getTable();
    }

    public function getModel(): Model
    {
        return $this->repository->getModel();
    }

    public function all(): Collection
    {
        return Cache::tags([$this->cacheTag])->remember(
            'all',
            $this->cacheTTL,
            fn () => $this->repository->all(),
        );
    }

    public function filter(array $filteredData = []): LengthAwarePaginator
    {
        $cacheKey = $this->generateCacheKey($filteredData);

        return Cache::tags([$this->cacheTag])->remember(
            $cacheKey,
            $this->cacheTTL,
            fn () => $this->repository->filter($filteredData),
        );
    }

    public function show(array $filteredData = [])
    {
        $cacheKey = $this->generateCacheKey($filteredData);

        return Cache::tags([$this->cacheTag])->remember(
            $cacheKey,
            $this->cacheTTL,
            fn () => $this->repository->show($filteredData),
        );
    }

    public function create(array $createdData = []): Model
    {
        $model = $this->repository->create($createdData);
        $this->flushCache();

        return $model;
    }

    public function update(array $updatedField = [], array $updatedData = []): Model
    {
        $result = $this->repository->update($updatedField, $updatedData);
        $this->flushCache();

        return $result;
    }

    public function delete(array $deletedField = []): int
    {
        $result = $this->repository->delete($deletedField);
        $this->flushCache();

        return $result;
    }

    protected function generateCacheKey(array $filters): string
    {
        ksort($filters);
        $uniqueKey = http_build_query($filters);

        return sprintf(
            '%s:%s:%s',
            $this->cacheTag,
            $this->getModel()->getTable(),
            md5($uniqueKey),
        );
    }

    protected function flushCache(): void
    {
        Cache::tags([$this->cacheTag])->flush();
    }
}

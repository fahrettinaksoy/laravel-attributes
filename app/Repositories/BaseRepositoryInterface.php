<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

interface BaseRepositoryInterface
{
    public function getModel(): Model;

    public function all(): Collection;

    public function filter(array $filteredData = []): LengthAwarePaginator;

    public function show(array $filteredData = []);

    public function create(array $createdData = []): Model;

    public function update(array $updatedField = [], array $updatedData = []): Model;

    public function delete(array $deletedField = []): int;
}

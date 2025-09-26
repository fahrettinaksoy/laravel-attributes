<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\Main\MainActionDestroy;
use App\Actions\Main\MainActionFilter;
use App\Actions\Main\MainActionShow;
use App\Actions\Main\MainActionStore;
use App\Actions\Main\MainActionUpdate;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BaseService
{
    protected array $actions;

    public function __construct(
        protected BaseRepositoryInterface $repository,
    ) {
        $this->actions = [
            'filter' => new MainActionFilter($this->repository),
            'show' => new MainActionShow($this->repository),
            'store' => new MainActionStore($this->repository),
            'update' => new MainActionUpdate($this->repository),
            'destroy' => new MainActionDestroy($this->repository),
        ];
    }

    public static function make(BaseRepositoryInterface $repository): self
    {
        return new self($repository);
    }

    public function getModel(): Model
    {
        return $this->repository->getModel();
    }

    public function filter(array $filter)
    {
        return $this->actions['filter']->execute($filter);
    }

    public function show(array $filter)
    {
        $id = request()->route('id');
        $model = $this->actions['show']->execute((int) $id);

        return $model->fresh();
    }

    public function store(array $data)
    {
        DB::beginTransaction();

        try {
            $model = $this->actions['store']->execute($data);
            DB::commit();

            return $model->fresh();
        } catch (\Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    public function update(array $data)
    {
        DB::beginTransaction();

        try {
            $id = request()->route('id');

            $model = $this->actions['update']->execute((int) $id, $data);
            DB::commit();

            return $model;
        } catch (\Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    public function destroy(array $deleteFilter)
    {
        DB::beginTransaction();

        try {
            $deleted = $this->actions['destroy']->execute($deleteFilter);
            DB::commit();

            return $deleted;
        } catch (\Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }
}

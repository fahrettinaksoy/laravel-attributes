<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\Pivot\PivotActionDestroy;
use App\Actions\Pivot\PivotActionFilter;
use App\Actions\Pivot\PivotActionShow;
use App\Actions\Pivot\PivotActionStore;
use App\Actions\Pivot\PivotActionUpdate;
use App\Exceptions\BusinessLogicException;
use App\Exceptions\NotFoundException;
use App\Factories\ModelFactory;
use App\Repositories\PivotRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class PivotService
{
    protected array $actions;

    protected PivotRepository $pivotRepository;

    public function __construct(
        protected ModelFactory $modelFactory,
        PivotRepository $pivotRepository,
    ) {
        $this->pivotRepository = $pivotRepository;
        $this->actions = [
            'filter' => new PivotActionFilter($this->pivotRepository),
            'show' => new PivotActionShow($this->pivotRepository),
            'store' => new PivotActionStore($this->pivotRepository),
            'update' => new PivotActionUpdate($this->pivotRepository),
            'destroy' => new PivotActionDestroy($this->pivotRepository),
        ];
    }

    public function filter(int $parentId, string $relationName, array $filter)
    {
        $this->setupPivotContext($parentId, $relationName);

        try {
            return $this->actions['filter']->execute($filter);
        } catch (ModelNotFoundException $e) {
            throw new NotFoundException(__('exceptions/filter.not_found'));
        } catch (\Exception $e) {
            throw new BusinessLogicException(
                message: __('exceptions/filter.generic_error'),
                context: ['parent_id' => $parentId, 'relation' => $relationName, 'filter' => $filter],
                errorCode: 'PIVOT_FILTER_OPERATION_FAILED',
            );
        }
    }

    public function show(int $parentId, string $relationName, int $relationId, array $filter)
    {
        try {
            $this->setupPivotContext($parentId, $relationName);

            return $this->actions['show']->execute($relationId);
        } catch (ModelNotFoundException $e) {
            throw new NotFoundException(__('exceptions/show.not_found'));
        } catch (\Exception $e) {
            throw new BusinessLogicException(
                message: $e->getMessage(),
                context: ['parent_id' => $parentId, 'relation' => $relationName, 'relation_id' => $relationId],
                errorCode: 'PIVOT_SHOW_OPERATION_FAILED',
            );
        }
    }

    public function store(int $parentId, string $relationName, array $data)
    {
        DB::beginTransaction();

        try {
            $this->setupPivotContext($parentId, $relationName);

            $data[$this->getParentForeignKey($relationName)] = $parentId;

            $model = $this->actions['store']->execute($data);
            DB::commit();

            return $model->fresh();
        } catch (ModelNotFoundException $e) {
            DB::rollBack();

            throw new NotFoundException(__('exceptions/store.not_found'));
        } catch (\Exception $e) {
            DB::rollBack();

            throw new BusinessLogicException(
                message: $e->getMessage(),
                context: ['parent_id' => $parentId, 'relation' => $relationName, 'data' => $data],
                errorCode: 'PIVOT_STORE_OPERATION_FAILED',
            );
        }
    }

    public function update(int $parentId, string $relationName, int $relationId, array $data)
    {
        DB::beginTransaction();

        try {
            $this->setupPivotContext($parentId, $relationName);

            $model = $this->actions['update']->execute($relationId, $data);
            DB::commit();

            return $model;
        } catch (ModelNotFoundException $e) {
            DB::rollBack();

            throw new NotFoundException(__('exceptions/update.not_found'));
        } catch (\Exception $e) {
            DB::rollBack();

            throw new BusinessLogicException(
                message: $e->getMessage(),
                context: ['parent_id' => $parentId, 'relation' => $relationName, 'relation_id' => $relationId],
                errorCode: 'PIVOT_UPDATE_OPERATION_FAILED',
            );
        }
    }

    public function destroy(int $parentId, string $relationName, int $relationId, array $data = [])
    {
        DB::beginTransaction();

        try {
            $this->setupPivotContext($parentId, $relationName);

            $deletedCount = $this->actions['destroy']->execute($relationId);
            DB::commit();

            return $deletedCount;
        } catch (ModelNotFoundException $e) {
            DB::rollBack();

            throw new NotFoundException(__('exceptions/destroy.not_found'));
        } catch (\Exception $e) {
            DB::rollBack();

            throw new BusinessLogicException(
                message: $e->getMessage(),
                context: ['parent_id' => $parentId, 'relation' => $relationName, 'relation_id' => $relationId],
                errorCode: 'PIVOT_DESTROY_OPERATION_FAILED',
            );
        }
    }

    private function setupPivotContext(int $parentId, string $relationName): void
    {
        $request = request();
        $parentModelClass = $request->attributes->get('parentModelClass');
        $pivotModelClass = $request->attributes->get('pivotModelClass');
        $parentModel = $this->modelFactory->create($parentModelClass);
        $parentInstance = $parentModel::findOrFail($parentId);

        $pivotModel = $this->modelFactory->create($pivotModelClass);
        $this->pivotRepository->setModel($pivotModel);
        $this->pivotRepository->setParentModel($parentInstance);
        $this->pivotRepository->setRelationName($relationName);
    }

    private function getParentForeignKey(string $relationName): string
    {
        $request = request();
        $tableName = $request->attributes->get('tableName');

        return $tableName.'_id';
    }
}

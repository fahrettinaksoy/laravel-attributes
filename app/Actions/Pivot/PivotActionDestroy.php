<?php

declare(strict_types=1);

namespace App\Actions\Pivot;

use App\Actions\BaseAction;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PivotActionDestroy extends BaseAction
{
    public function execute(int $relationId): int
    {
        $primaryKey = $this->repository->getModel()->getKeyName();
        $filter = [$primaryKey => $relationId];

        $deleted = $this->repository->delete($filter);

        if (! $deleted) {
            throw new ModelNotFoundException(__('actions/base.error.delete'));
        }

        return $deleted;
    }
}

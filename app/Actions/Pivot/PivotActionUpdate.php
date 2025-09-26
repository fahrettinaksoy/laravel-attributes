<?php

declare(strict_types=1);

namespace App\Actions\Pivot;

use App\Actions\BaseAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PivotActionUpdate extends BaseAction
{
    public function execute(int $relationId, array $data): Model
    {
        $primaryKey = $this->repository->getModel()->getKeyName();
        $filter = [$primaryKey => $relationId];

        $updated = $this->repository->update($filter, $data);

        if (! $updated) {
            throw new ModelNotFoundException(__('actions/base.error.update'));
        }

        return $updated;
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Pivot;

use App\Actions\BaseAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PivotActionShow extends BaseAction
{
    public function execute(int $relationId): Model
    {
        $primaryKey = $this->repository->getModel()->getKeyName();
        $filter = ['filter' => [$primaryKey => $relationId]];

        $showed = $this->repository->show($filter);

        if (! $showed) {
            throw new ModelNotFoundException(__('actions/base.error.show'));
        }

        return $showed;
    }
}

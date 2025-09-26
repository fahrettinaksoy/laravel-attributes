<?php

declare(strict_types=1);

namespace App\Actions\Main;

use App\Actions\BaseAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class MainActionUpdate extends BaseAction
{
    public function execute(int $id, array $data): Model
    {
        $primaryKey = $this->repository->getModel()->getKeyName();
        $filter = [$primaryKey => $id];

        $updated = $this->repository->update($filter, $data);

        if (! $updated) {
            throw new ModelNotFoundException(__('actions/base.error.update'));
        }

        return $updated;
    }

    /*
    public function executeWithFilter(array $filter, array $data): Model
    {
        $updated = $this->repository->update($filter, $data);

        if (! $updated) {
            throw new ModelNotFoundException(__('actions/base.error.update'));
        }

        return $updated;
    }
    */
}

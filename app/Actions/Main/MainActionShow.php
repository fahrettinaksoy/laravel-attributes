<?php

declare(strict_types=1);

namespace App\Actions\Main;

use App\Actions\BaseAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class MainActionShow extends BaseAction
{
    public function execute(int $id): Model
    {
        $primaryKey = $this->repository->getModel()->getKeyName();
        $filter = ['filter' => [$primaryKey => $id]];

        $showed = $this->repository->show($filter);

        if (! $showed) {
            throw new ModelNotFoundException(__('actions/base.error.show'));
        }

        return $showed;
    }

    /*
    public function executeWithFilter(array $filter): Model
    {
        $showed = $this->repository->show($filter);

        if (! $showed) {
            throw new ModelNotFoundException(__('actions/base.error.show'));
        }

        return $showed;
    }
    */
}

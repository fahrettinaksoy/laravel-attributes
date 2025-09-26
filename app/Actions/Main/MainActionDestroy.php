<?php

declare(strict_types=1);

namespace App\Actions\Main;

use App\Actions\BaseAction;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class MainActionDestroy extends BaseAction
{
    public function execute(array $filter): int
    {
        $deleted = $this->repository->delete($filter);

        if (!$deleted) {
            throw new ModelNotFoundException(__('actions/base.error.delete'));
        }

        return $deleted;
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Main;

use App\Actions\BaseAction;

class MainActionFilter extends BaseAction
{
    public function execute(array $filter)
    {
        return $this->repository->filter($filter);
    }
}

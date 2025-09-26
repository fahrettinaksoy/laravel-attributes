<?php

declare(strict_types=1);

namespace App\Actions\Pivot;

use App\Actions\BaseAction;

class PivotActionFilter extends BaseAction
{
    public function execute(array $filter)
    {
        return $this->repository->filter($filter);
    }
}

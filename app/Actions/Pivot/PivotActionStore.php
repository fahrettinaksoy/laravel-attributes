<?php

declare(strict_types=1);

namespace App\Actions\Pivot;

use App\Actions\BaseAction;

class PivotActionStore extends BaseAction
{
    public function execute(array $data)
    {
        return $this->repository->create($data);
    }
}

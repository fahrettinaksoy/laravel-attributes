<?php

declare(strict_types=1);

namespace App\Actions\Main;

use App\Actions\BaseAction;

class MainActionStore extends BaseAction
{
    public function execute(array $data)
    {
        return $this->repository->create($data);
    }
}

<?php

declare(strict_types=1);

namespace App\Actions;

use App\Repositories\BaseRepositoryInterface;

abstract class BaseAction
{
    public function __construct(
        protected BaseRepositoryInterface $repository,
    ) {}
}

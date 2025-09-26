<?php

declare(strict_types=1);

namespace App\Http\Resources\Parameter;

use App\Http\Resources\BaseResource;

class ParameterResource extends BaseResource
{
    public function toArray($request): array
    {
        return $this->resource;
    }
}

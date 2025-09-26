<?php

declare(strict_types=1);

namespace App\Http\Resources\Parameter;

use App\Http\Resources\BaseCollection;

class ParameterCollection extends BaseCollection
{
    public $collects = ParameterResource::class;

    public function toArray($request): array
    {
        return [
            'data' => $this->collection,
        ];
    }
}

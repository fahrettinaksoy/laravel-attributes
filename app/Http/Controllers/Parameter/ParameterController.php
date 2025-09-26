<?php

declare(strict_types=1);

namespace App\Http\Controllers\Parameter;

use App\Http\Controllers\Controller;
use App\Http\Resources\Parameter\ParameterResource;
use App\Traits\ScansModulesTrait;
use Illuminate\Http\JsonResponse;

class ParameterController extends Controller
{
    use ScansModulesTrait;

    public function index(): JsonResponse
    {
        $moduleResults = $this->getModules();

        return (new ParameterResource($moduleResults))
            ->response()
            ->setStatusCode(200);
    }
}

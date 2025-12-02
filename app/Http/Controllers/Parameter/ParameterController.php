<?php

declare(strict_types=1);

namespace App\Http\Controllers\Parameter;

use App\Http\Controllers\Controller;
use App\Http\Resources\Parameter\ParameterResource;
use App\Services\Module\ModuleScannerService;
use Illuminate\Http\JsonResponse;

class ParameterController extends Controller
{
    public function __construct(
        private readonly ModuleScannerService $moduleScanner
    ) {}

    public function index(): JsonResponse
    {
        $moduleResults = $this->moduleScanner->getModules(true);

        return (new ParameterResource($moduleResults))
            ->response()
            ->setStatusCode(200);
    }
}

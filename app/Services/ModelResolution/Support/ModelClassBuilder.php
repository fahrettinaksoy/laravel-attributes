<?php

declare(strict_types=1);

namespace App\Services\ModelResolution\Support;

use Illuminate\Support\Str;

final readonly class ModelClassBuilder
{
    public function __construct(
        private string $baseNamespace = 'App\\Models',
        private string $modelSuffix = 'Model',
    ) {
    }

    public function build(array $pathSegments, ?string $customName = null): string
    {
        $nsParts = array_map([Str::class, 'studly'], $pathSegments);
        $namespace = $this->baseNamespace.'\\'.implode('\\', $nsParts);
        $className = $customName ?: (Str::studly(end($pathSegments)).$this->modelSuffix);

        return $namespace.'\\'.$className;
    }
}

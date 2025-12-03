<?php

declare(strict_types=1);

namespace App\Services\ModelResolution\Support;

final readonly class ExceptionRouteChecker
{
    /**
     * @param  array<string>  $exceptionRoutes
     */
    public function __construct(
        private array $exceptionRoutes
    ) {
    }

    public function isException(string $path): bool
    {
        foreach ($this->exceptionRoutes as $exceptionRoute) {
            if (str_starts_with($path, $exceptionRoute)) {
                return true;
            }
        }

        return false;
    }
}

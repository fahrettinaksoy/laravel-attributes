<?php

declare(strict_types=1);

namespace App\Services\Module;

use Illuminate\Database\Eloquent\Relations\HasMany;
use ReflectionClass;
use ReflectionMethod;
use Throwable;

final class ModuleRelationDetectorService
{
    private array $cache = [];

    public function getHasManyRelations(object $model): array
    {
        $class = get_class($model);

        if (!isset($this->cache[$class])) {
            $this->cache[$class] = $this->detectHasManyRelations($model);
        }

        return $this->cache[$class];
    }

    private function detectHasManyRelations(object $model): array
    {
        $methods = [];
        $reflect = new ReflectionClass($model);
        $class = get_class($model);

        foreach ($reflect->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->class !== $class || $method->getNumberOfParameters() !== 0) {
                continue;
            }

            try {
                $relation = $method->invoke($model);
                if ($relation instanceof HasMany) {
                    $methods[] = $method->getName();
                }
            } catch (Throwable $e) {}
        }

        return $methods;
    }

    public function clearCache(): void
    {
        $this->cache = [];
    }
}

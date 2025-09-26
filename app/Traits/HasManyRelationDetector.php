<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use ReflectionClass;
use ReflectionMethod;

trait HasManyRelationDetector
{
    protected static array $hasManyCache = [];

    protected function getHasManyRelationMethods(Model $model): array
    {
        $class = get_class($model);

        if (! isset(self::$hasManyCache[$class])) {
            $methods = [];
            $reflect = new ReflectionClass($model);

            foreach ($reflect->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->class !== $class || $method->getNumberOfParameters() !== 0) {
                    continue;
                }

                try {
                    $relation = $method->invoke($model);
                    if ($relation instanceof HasMany) {
                        $methods[] = $method->getName();
                    }
                } catch (\Throwable $e) {
                }
            }

            self::$hasManyCache[$class] = $methods;
        }

        return self::$hasManyCache[$class];
    }
}

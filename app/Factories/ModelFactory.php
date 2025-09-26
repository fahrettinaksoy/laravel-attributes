<?php

namespace App\Factories;

use Illuminate\Database\Eloquent\Model;

class ModelFactory
{
    public function create(string $modelClass): Model
    {
        if (! class_exists($modelClass)) {
            throw new \InvalidArgumentException("Model class '{$modelClass}' does not exist");
        }

        $instance = new $modelClass;

        if (! ($instance instanceof Model)) {
            throw new \InvalidArgumentException("Class '{$modelClass}' is not an Eloquent Model");
        }

        return $instance;
    }
}

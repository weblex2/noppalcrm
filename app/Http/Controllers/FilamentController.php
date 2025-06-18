<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\Relation;

class FilamentController extends Controller
{
    function checkIfRelationExists(array $config): bool
    {
        $sourceClass = $this->modelForTable(Str::plural($config['source']));
        $targetClass = $this->modelForTable(Str::plural($config['target']));
        $relationType = $config['method'];

        $modelClass = \App\Models\Contact::class;

        return $this->modelHasRelation($modelClass, 'company', 'BelongsTo', \App\Models\Company::class);
    }

    function modelForTable(string $table): ?string
    {
        foreach (get_declared_classes() as $class) {
            if (is_subclass_of($class, \Illuminate\Database\Eloquent\Model::class)) {
                $model = new $class;
                if ($model->getTable() === $table) {
                    return $class;
                }
            }
        }

        return null;
    }

    function modelHasRelation(string $modelClass, string $methodName, string $expectedType = null, string $targetModel = null): bool
    {
        if (!method_exists($modelClass, $methodName)) {
            return false;
        }

        try {
            $model = new $modelClass;
            $relation = $model->$methodName();

            if (!($relation instanceof \Illuminate\Database\Eloquent\Relations\Relation)) {
                return false;
            }

            if ($expectedType && strtolower(class_basename($relation)) !== strtolower($expectedType)) {
                return false;
            }

            if ($targetModel && get_class($relation->getRelated()) !== $targetModel) {
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}

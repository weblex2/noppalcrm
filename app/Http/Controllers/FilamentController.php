<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Database\Eloquent\Relations\Relation;

class FilamentController extends Controller
{
    function checkIfRelationExists(array $config): bool
    {
        $sourceClass = $this->modelForTable(Str::plural($config['source']));
        $targetClass = $this->modelForTable(Str::plural($config['target']));
        $relationType = $config['method'];
        $relationName = $config['relation_name'];

        $relationsAlreadyExists =  $this->modelHasRelation($sourceClass, $relationName, $relationType, $targetClass);
        if (!$relationsAlreadyExists){
            return $this->createRelation($config, $targetClass);
        }
        else{
            return true;
        }
    }

    private function createRelation($config, $targetClass) :bool{
        // get the trait file
        $traitName = ucfirst(Str::singular($config['source']))."Relations";
        $targetPath = app_path("Traits/{$traitName}.php");
        $stubPath = app_path('Filament/stubs/filament/relations/'.$config['method'].'.stub');
        if (!file_exists($stubPath)){
             throw new \Exception("Stub-Datei nicht gefunden: {$stubPath}");
        }
        $stubContent = File::get($stubPath);
        $stubContent = str_replace('{{targetClass}}', "\\".$targetClass, $stubContent);
        $stubContent = str_replace('{{field}}', $config['field'], $stubContent);
        $stubContent .= "\n\n\t##";
        $targetContent = File::get($targetPath);
        $targetContent = str_replace('##', $stubContent, $targetContent);
        File::put($targetPath, $targetContent);
        return true;
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

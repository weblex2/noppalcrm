<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Models\FilamentAction;
use App\Models\FilamentConfig;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\TextInput;

class FilamentController extends Controller
{
    function checkIfRelationExists(array $config): bool
    {
        $sourceClass = "\\App\\Models\\". Str::studly(Str::singular($config['source']));
        $targetClass = "\\App\\Models\\". Str::studly(Str::singular($config['target']));
        $relationType = $config['method'];
        $relationName = $config['relation_name'];

        if (!$this->traitExists(Str::singular($config['source']))){
            $this->generateTrait(Str::singular($config['source']));
        }
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
        $traitName = Str::studly(Str::singular($config['source']))."Relations";
        $targetPath = app_path("Traits/{$traitName}.php");
        $stubPath = app_path('Filament/stubs/filament/relations/'.$config['method'].'.stub');
        if (!file_exists($stubPath)){
             throw new \Exception("Stub-Datei nicht gefunden: {$stubPath}");
        }
        $stubContent = File::get($stubPath);
        $stubContent = str_replace('{{functionName}}', $config['relation_name'], $stubContent);
        $stubContent = str_replace('{{targetClass}}', $targetClass, $stubContent);
        $stubContent = str_replace('{{field}}', $config['field'], $stubContent);
        $stubContent .= "\n\n\t##";
        $targetContent = File::get($targetPath);
        $targetContent = str_replace('##', $stubContent, $targetContent);
        File::put($targetPath, $targetContent);
        return true;
    }

    public function traitExists($baseName){
        $traitName = "{$baseName}Relations";
        $targetPath = app_path("Traits/{$traitName}.php");
        return file_exists($targetPath);
    }
    protected function generateTrait($baseName){
        $traitName = "{$baseName}Relations";
        $stubPath = base_path('app/Filament/stubs/filament/relations/traitContent.stub');
        $targetPath = app_path("Traits/{$traitName}.php");

        // Stub lesen
        if (!File::exists($stubPath)) {
            throw new \Exception("Stub-Datei nicht gefunden: {$stubPath}");
        }

        $stubContent = File::get($stubPath);

        // Platzhalter ersetzen
        $traitContent = str_replace('{{Model}}', $baseName, $stubContent);

        // Zielverzeichnis anlegen, falls nötig
        $targetDir = dirname($targetPath);
        if (!File::exists($targetDir)) {
            File::makeDirectory($targetDir, 0755, true);
        }

        // Trait-Datei schreiben
        if (!file_exists($targetPath)){
            File::put($targetPath, $traitContent);
            return true;
        }
        return false;
    }



    function modelHasRelation(string $modelClass, string $methodName, string $expectedType, string $targetModel): bool
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

            /* if ("\\".get_class($relation->getRelated()) !== $targetModel) {
                return false;
            } */
            if (strcasecmp(get_class($relation->getRelated()), ltrim($targetModel, '\\')) !== 0) {
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function getTableFilter($resource){
        // Dynamische Filter generieren
        $filterGroups = FilamentConfig::getFiltersFor($resource);
        $filters = [];

        foreach ($filterGroups as $field => $options) {
            $filters[] = SelectFilter::make($field)->options($options);
        }

        return $filters;
    }
}

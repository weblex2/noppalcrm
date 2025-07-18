<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Models\FilamentAction;
use Filament\Facades\Filament;
use App\Models\FilamentConfig;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

    public static function getTableFilter($resource)
    {
        // Dynamische Filter generieren
        $filterGroups = FilamentConfig::getFiltersFor($resource);
        $filters = [];

        foreach ($filterGroups as $field => $options) {
            $filters[] = SelectFilter::make($field)->options($options);
        }

        return $filters;
    }

    public static function getResourcesDropdown($incRelMan = true, $incPages = true): array
    {
        $resources = Filament::getResources();
        $tables = [];

        foreach ($resources as $resourceClass) {
            if (!method_exists($resourceClass, 'getModel')) {
                continue;
            }

            $modelClass = $resourceClass::getModel();

            if (!class_exists($modelClass)) {
                continue;
            }

            // Resource selbst
            $model = new $modelClass();
            $table = Str::singular($model->getTable());
            $label = $resourceClass::getPluralLabel() ?: Str::headline(class_basename($modelClass));
            $key = Str::studly($table) . 'Resource';

            $tables[$key] = $label;

            // RelationManagers einbeziehen
            if ($incRelMan){
                if (method_exists($resourceClass, 'getRelations')) {
                    foreach ($resourceClass::getRelations() as $relationManagerClass) {
                        if (!class_exists($relationManagerClass)) {
                            continue;
                        }

                        try {
                            $reflection = new \ReflectionClass($relationManagerClass);
                            $property = $reflection->getProperty('relationship');
                            $property->setAccessible(true);
                            $relationName = $property->getValue();

                            if (!method_exists($modelClass, $relationName)) {
                                continue;
                            }

                            $relation = (new $modelClass)->{$relationName}();
                            if (!$relation instanceof \Illuminate\Database\Eloquent\Relations\Relation) {
                                continue;
                            }

                            $relatedModel = $relation->getRelated();
                            $relatedTable = Str::singular($relatedModel->getTable());
                            $relatedLabel = Str::headline(Str::plural(class_basename($relatedModel)));

                            // Key und Label zusammensetzen
                            $relationKey = Str::studly($table) . 'Resource::' . $relationName;
                            $relationLabel = $label . ' → ' . $relatedLabel;

                            $tables[$relationKey] = $relationLabel;
                        } catch (\Throwable $e) {
                            \Log::channel('crm')->warning("Fehler bei RelationManager $relationManagerClass: " . $e->getMessage());
                        }
                    }
                }
            }
        }

        if ($incPages){
            $pageFiles = \File::files(app_path('Filament/Pages'));

            foreach ($pageFiles as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $className = 'App\\Filament\\Pages\\' . $file->getFilenameWithoutExtension();

                if (!class_exists($className)) {
                    continue;
                }

                $base = class_basename($className);
                $label = Str::headline($base);
                $key = 'Page::' . $base;

                $tables[$key] = $label;
            }
        }

        asort($tables);
        return $tables;
    }

    public static function getResourcesFieldDropdown(?string $resourceClass): array
    {
        if (!$resourceClass) {
            return [];
        }

        $fullClass = 'App\\Filament\\Resources\\' . $resourceClass;

        if (class_exists($fullClass) && method_exists($fullClass, 'getModel')) {
            $modelClass = $fullClass::getModel();
            $modelInstance = new $modelClass(); // ✅ Instanziieren
            $table = $modelInstance->getTable(); // ✅ Aufrufen
            #echo $tableName;
        }

        // Prüfen, ob es sich um einen RelationManager-Eintrag handelt
        /* if (str_contains($resourceClass, '::')) {
            [$relationName, $table] = explode('::', $resourceClass, 2);
        } else {
            $table = $tableName;
        } */

        if (!Schema::hasTable($table)) {
            return [];
        }

        return collect(Schema::getColumnListing($table))
            ->mapWithKeys(fn ($column) => [$column => $column ?? 'YYY'])
            ->toArray();
    }

    public static function getNavigationGroups(){
        return ['' => '<none>'] + DB::table('resource_configs')
                        ->whereNotNull('navigation_group')
                        ->distinct()
                        ->orderBy('navigation_group')
                        ->pluck('navigation_group', 'navigation_group')
                        ->toArray();
    }
}

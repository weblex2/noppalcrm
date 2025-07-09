<?php

namespace App\Console\Commands;

use Filament\Clusters\Cluster;
use Filament\Facades\Filament;
use Filament\Forms\Commands\Concerns\CanGenerateForms;
use Filament\Panel;
use Filament\Support\Commands\Concerns\CanIndentStrings;
use Filament\Support\Commands\Concerns\CanManipulateFiles;
use Filament\Support\Commands\Concerns\CanReadModelSchemas;
use Filament\Tables\Commands\Concerns\CanGenerateTables;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Symfony\Component\Console\Attribute\AsCommand;
use Illuminate\Support\Facades\File;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

#[AsCommand(name: 'make:custom-filament-resource')]
class MakeCustomFilamentResourceCommand extends Command
{
    use CanGenerateForms;
    use CanGenerateTables;
    use CanIndentStrings;
    use CanManipulateFiles;
    use CanReadModelSchemas;

    protected $description = 'Create a new Filament resource class and default page classes';

    protected $signature = 'make:custom-filament-resource {name?} {--model-namespace=} {--soft-deletes} {--view} {--G|generate} {--S|simple} {--panel=} {--model} {--migration} {--factory} {--F|force}';

    protected function copyStubToApp(string $stubName, string $destination, array $replacements = []): int
    {
        // Pfad zu deinen eigenen Stub-Dateien (hier im Beispiel: resources/stubs/filament)
        //$stubPath = "stubs/filament/{$stubName}.stub";
        $stubPath = base_path("app/Filament/stubs/filament/{$stubName}.stub");
        if (! file_exists($stubPath)) {
            $this->error("Stub-Datei nicht gefunden: {$stubPath}");
            return self::FAILURE;
        }

        $stubContent = file_get_contents($stubPath);

        // Ersetze Platzhalter im Stub
        foreach ($replacements as $key => $value) {
            #$stubContent = str_replace("{{{$key}}}", $value, $stubContent);
            $stubContent = preg_replace('/{{\s*' . preg_quote($key, '/') . '\s*}}/', $value, $stubContent);
        }

        // Sicherstellen, dass das Zielverzeichnis existiert
        $directory = dirname($destination);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Stub-Inhalt in die Zieldatei schreiben
        file_put_contents($destination, $stubContent);
        return self::SUCCESS;
    }

    public function handle(): int
    {
        $model = (string) str($this->argument('name') ?? text(
            label: 'What is the model name?',
            placeholder: 'BlogPost',
            required: true,
        ))
            ->studly()
            ->beforeLast('Resource')
            ->trim('/')
            ->trim('\\')
            ->trim(' ')
            ->studly()
            ->replace('/', '\\');

        if (blank($model)) {
            $model = 'Resource';
        }

        $modelNamespace = $this->option('model-namespace') ?? 'App\\Models';

        if ($this->option('model')) {
            $this->callSilently('make:model', [
                'name' => "{$modelNamespace}\\{$model}",
            ]);

            // Pfad zur Model-Datei ermitteln (unter app/Standard oder Namespace)
            $modelPath = app_path('Models/'.str_replace('\\', '/', $model) . '.php');

            if (file_exists($modelPath)) {
                $res = $this->generateTrait($model);
                $content = file_get_contents($modelPath);

                // 1. use-Zeile einfügen, wenn nicht vorhanden
                $traitName = $model . 'Relations';
                $useLine = "use App\\Traits\\{$traitName};";

                if (!str_contains($content, $useLine)) {
                    // Einfügen nach dem letzten use-Statement oder nach namespace
                    if (preg_match('/(namespace\s+.*?;)(.*?)(use\s+[^\n]+;)?/s', $content, $matches)) {
                        $replacement = $matches[1] . "\n\n" . $useLine . "\n" . $matches[2] . ($matches[3] ?? '');
                        $content = preg_replace('/namespace\s+.*?;.*?(use\s+[^\n]+;)?/s', $replacement, $content, 1);
                    }
                }

                // 2. use-Trait innerhalb der Klasse ergänzen
                if (!str_contains($content, "use {$traitName};")) {
                    $content = preg_replace(
                        '/class\s+' . preg_quote($model) . '\s+extends\s+Model\s*\{/',
                        "class {$model} extends Model\n{\n    use {$traitName};",
                        $content,
                        1
                    );
                }

                // 3. $guarded ergänzen (wie bisher)
                if (!str_contains($content, 'protected $guarded')) {
                    $content = preg_replace(
                        '/use\s+' . preg_quote($traitName) . '\s*;/',
                        "use {$traitName};\n    protected \$guarded = ['id'];",
                        $content,
                        1
                    );
                }

                file_put_contents($modelPath, $content);
            }
        }

        if ($this->option('migration')) {
            $table = (string) str($model)
                ->classBasename()
                ->pluralStudly()
                ->snake();

            $this->call('make:migration', [
                'name' => "create_{$table}_table",
                '--create' => $table,
            ]);

            // Migrationsordner
            $migrationPath = database_path('migrations');

            // Alle Dateien im migrations-Ordner holen
            $files = collect(glob($migrationPath . DIRECTORY_SEPARATOR . "*create_{$table}_table.php"));

            if ($files->isEmpty()) {
                $this->error("Migration für Tabelle {$table} nicht gefunden.");
                return Command::FAILURE;
            }

            // Neueste Migration-Datei nehmen (nach Timestamp sortiert)
            $migrationFile = $files->sort()->last();

            // Relativen Pfad für Artisan migrate --path benötigen (ab database/)
            $relativePath = 'database/migrations/' . basename($migrationFile);

            // Nur diese eine Migration ausführen
            $this->call('migrate', [
                '--path' => $relativePath,
            ]);
        }

        if ($this->option('factory')) {
            $this->callSilently('make:factory', [
                'name' => $model,
            ]);
        }

        $modelClass = (string) str($model)->afterLast('\\');
        $modelSubNamespace = str($model)->contains('\\') ?
            (string) str($model)->beforeLast('\\') :
            '';
        $pluralModelClass = (string) str($modelClass)->pluralStudly();
        $needsAlias = $modelClass === 'Record';

        $panel = $this->option('panel');

        if ($panel) {
            $panel = Filament::getPanel($panel, isStrict: false);
        }

        if (! $panel) {
            $panels = Filament::getPanels();

            /** @var Panel $panel */
            $panel = (count($panels) > 1) ? $panels[select(
                label: 'Which panel would you like to create this in?',
                options: array_map(
                    fn (Panel $panel): string => $panel->getId(),
                    $panels,
                ),
                default: Filament::getDefaultPanel()->getId()
            )] : Arr::first($panels);
        }

        $resourceDirectories = $panel->getResourceDirectories();
        $resourceNamespaces = $panel->getResourceNamespaces();

        foreach ($resourceDirectories as $resourceIndex => $resourceDirectory) {
            if (str($resourceDirectory)->startsWith(base_path('vendor'))) {
                unset($resourceDirectories[$resourceIndex]);
                unset($resourceNamespaces[$resourceIndex]);
            }
        }

        $namespace = (count($resourceNamespaces) > 1) ?
            select(
                label: 'Which namespace would you like to create this in?',
                options: $resourceNamespaces
            ) :
            (Arr::first($resourceNamespaces) ?? 'App\\Filament\\Resources');
        $path = (count($resourceDirectories) > 1) ?
            $resourceDirectories[array_search($namespace, $resourceNamespaces)] :
            (Arr::first($resourceDirectories) ?? app_path('Filament/Resources/'));

        $resource = "{$model}Resource";
        $resourceClass = "{$modelClass}Resource";
        $resourceNamespace = $modelSubNamespace;
        $namespace .= $resourceNamespace !== '' ? "\\{$resourceNamespace}" : '';
        $listResourcePageClass = "List{$pluralModelClass}";
        $manageResourcePageClass = "Manage{$pluralModelClass}";
        $createResourcePageClass = "Create{$modelClass}";
        $editResourcePageClass = "Edit{$modelClass}";
        $viewResourcePageClass = "View{$modelClass}";

        $baseResourcePath =
            (string) str($resource)
                ->prepend('/')
                ->prepend($path)
                ->replace('\\', '/')
                ->replace('//', '/');

        $resourcePath = "{$baseResourcePath}.php";
        $resourcePagesDirectory = "{$baseResourcePath}/Pages";
        $listResourcePagePath = "{$resourcePagesDirectory}/{$listResourcePageClass}.php";
        $manageResourcePagePath = "{$resourcePagesDirectory}/{$manageResourcePageClass}.php";
        $createResourcePagePath = "{$resourcePagesDirectory}/{$createResourcePageClass}.php";
        $editResourcePagePath = "{$resourcePagesDirectory}/{$editResourcePageClass}.php";
        $viewResourcePagePath = "{$resourcePagesDirectory}/{$viewResourcePageClass}.php";

        if (! $this->option('force') && $this->checkForCollision([
            $resourcePath,
            $listResourcePagePath,
            $manageResourcePagePath,
            $createResourcePagePath,
            $editResourcePagePath,
            $viewResourcePagePath,
        ])) {
            return static::INVALID;
        }

        $pages = '';
        $pages .= '\'index\' => Pages\\' . ($this->option('simple') ? $manageResourcePageClass : $listResourcePageClass) . '::route(\'/\'),';

        if (! $this->option('simple')) {
            $pages .= PHP_EOL . "'create' => Pages\\{$createResourcePageClass}::route('/create'),";

            if ($this->option('view')) {
                $pages .= PHP_EOL . "'view' => Pages\\{$viewResourcePageClass}::route('/{record}'),";
            }

            $pages .= PHP_EOL . "'edit' => Pages\\{$editResourcePageClass}::route('/{record}/edit'),";
        }

        $tableActions = [];

        if ($this->option('view')) {
            $tableActions[] = 'Tables\Actions\ViewAction::make(),';
        }

        $tableActions[] = 'Tables\Actions\EditAction::make(),';

        $relations = '';

        if ($this->option('simple')) {
            $tableActions[] = 'Tables\Actions\DeleteAction::make(),';

            if ($this->option('soft-deletes')) {
                $tableActions[] = 'Tables\Actions\ForceDeleteAction::make(),';
                $tableActions[] = 'Tables\Actions\RestoreAction::make(),';
            }
        } else {
            $relations .= PHP_EOL . 'public static function getRelations(): array';
            $relations .= PHP_EOL . '{';
            $relations .= PHP_EOL . '    return [';
            $relations .= PHP_EOL . '        //';
            $relations .= PHP_EOL . '    ];';
            $relations .= PHP_EOL . '}' . PHP_EOL;
        }

        $tableActions = implode(PHP_EOL, $tableActions);

        $tableBulkActions = [];

        $tableBulkActions[] = 'Tables\Actions\DeleteBulkAction::make(),';

        $eloquentQuery = '';

        if ($this->option('soft-deletes')) {
            $tableBulkActions[] = 'Tables\Actions\ForceDeleteBulkAction::make(),';
            $tableBulkActions[] = 'Tables\Actions\RestoreBulkAction::make(),';

            $eloquentQuery .= PHP_EOL . PHP_EOL . 'public static function getEloquentQuery(): Builder';
            $eloquentQuery .= PHP_EOL . '{';
            $eloquentQuery .= PHP_EOL . '    return parent::getEloquentQuery()';
            $eloquentQuery .= PHP_EOL . '        ->withoutGlobalScopes([';
            $eloquentQuery .= PHP_EOL . '            SoftDeletingScope::class,';
            $eloquentQuery .= PHP_EOL . '        ]);';
            $eloquentQuery .= PHP_EOL . '}';
        }

        $tableBulkActions = implode(PHP_EOL, $tableBulkActions);

        $potentialCluster = (string) str($namespace)->beforeLast('\Resources');
        $clusterAssignment = null;
        $clusterImport = null;

        if (
            class_exists($potentialCluster) &&
            is_subclass_of($potentialCluster, Cluster::class)
        ) {
            $clusterAssignment = $this->indentString(PHP_EOL . PHP_EOL . 'protected static ?string $cluster = ' . class_basename($potentialCluster) . '::class;');
            $clusterImport = "use {$potentialCluster};" . PHP_EOL;
        }

        if (!isset($table)){
            $table = (string) str($model)
                ->classBasename()
                ->pluralStudly()
                ->snake();
        }

        $this->copyStubToApp('Resource', $resourcePath, [
            'clusterAssignment' => $clusterAssignment,
            'clusterImport' => $clusterImport,
            'eloquentQuery' => $this->indentString($eloquentQuery, 1),
            'formSchema' => $this->indentString($this->option('generate') ? $this->getResourceFormSchema(
                $modelNamespace . ($modelSubNamespace !== '' ? "\\{$modelSubNamespace}" : '') . '\\' . $modelClass,
            ) : '//', 4),
            ...$this->generateModel($model, $modelNamespace, $modelClass),
            'namespace' => $namespace,
            'table' => $table,
            'pages' => $this->indentString($pages, 3),
            'relations' => $this->indentString($relations, 1),
            'resource' => "{$namespace}\\{$resourceClass}",
            'resourceClass' => $resourceClass,
            'tableActions' => $this->indentString($tableActions, 4),
            'tableBulkActions' => $this->indentString($tableBulkActions, 5),
            'tableColumns' => $this->indentString($this->option('generate') ? $this->getResourceTableColumns(
                $modelNamespace . ($modelSubNamespace !== '' ? "\\{$modelSubNamespace}" : '') . '\\' . $modelClass,
            ) : '//', 4),
            'tableFilters' => $this->indentString(
                $this->option('soft-deletes') ? 'Tables\Filters\TrashedFilter::make(),' : '//',
                4,
            ),
        ]);

        if ($this->option('simple')) {
            $this->copyStubToApp('ResourceManagePage', $manageResourcePagePath, [
                'baseResourcePage' => 'Filament\\Resources\\Pages\\ManageRecords' . ($needsAlias ? ' as BaseManageRecords' : ''),
                'baseResourcePageClass' => $needsAlias ? 'BaseManageRecords' : 'ManageRecords',
                'namespace' => "{$namespace}\\{$resourceClass}\\Pages",
                'resource' => "{$namespace}\\{$resourceClass}",
                'resourceClass' => $resourceClass,
                'resourcePageClass' => $manageResourcePageClass,
            ]);
        } else {
            $this->copyStubToApp('ResourceListPage', $listResourcePagePath, [
                'baseResourcePage' => 'Filament\\Resources\\Pages\\ListRecords' . ($needsAlias ? ' as BaseListRecords' : ''),
                'baseResourcePageClass' => $needsAlias ? 'BaseListRecords' : 'ListRecords',
                'namespace' => "{$namespace}\\{$resourceClass}\\Pages",
                'resource' => "{$namespace}\\{$resourceClass}",
                'resourceClass' => $resourceClass,
                'resourcePageClass' => $listResourcePageClass,
            ]);

            $this->copyStubToApp('ResourcePage', $createResourcePagePath, [
                'baseResourcePage' => 'Filament\\Resources\\Pages\\CreateRecord' . ($needsAlias ? ' as BaseCreateRecord' : ''),
                'baseResourcePageClass' => $needsAlias ? 'BaseCreateRecord' : 'CreateRecord',
                'namespace' => "{$namespace}\\{$resourceClass}\\Pages",
                'resource' => "{$namespace}\\{$resourceClass}",
                'resourceClass' => $resourceClass,
                'resourcePageClass' => $createResourcePageClass,
            ]);

            $editPageActions = [];

            if ($this->option('view')) {
                $this->copyStubToApp('ResourceViewPage', $viewResourcePagePath, [
                    'baseResourcePage' => 'Filament\\Resources\\Pages\\ViewRecord' . ($needsAlias ? ' as BaseViewRecord' : ''),
                    'baseResourcePageClass' => $needsAlias ? 'BaseViewRecord' : 'ViewRecord',
                    'namespace' => "{$namespace}\\{$resourceClass}\\Pages",
                    'resource' => "{$namespace}\\{$resourceClass}",
                    'resourceClass' => $resourceClass,
                    'resourcePageClass' => $viewResourcePageClass,
                ]);

                $editPageActions[] = 'Actions\ViewAction::make(),';
            }

            $editPageActions[] = 'Actions\DeleteAction::make(),';

            if ($this->option('soft-deletes')) {
                $editPageActions[] = 'Actions\ForceDeleteAction::make(),';
                $editPageActions[] = 'Actions\RestoreAction::make(),';
            }

            $editPageActions = implode(PHP_EOL, $editPageActions);

            $this->copyStubToApp('ResourceEditPage', $editResourcePagePath, [
                'baseResourcePage' => 'Filament\\Resources\\Pages\\EditRecord' . ($needsAlias ? ' as BaseEditRecord' : ''),
                'baseResourcePageClass' => $needsAlias ? 'BaseEditRecord' : 'EditRecord',
                'actions' => $this->indentString($editPageActions, 3),
                'namespace' => "{$namespace}\\{$resourceClass}\\Pages",
                'resource' => "{$namespace}\\{$resourceClass}",
                'resourceClass' => $resourceClass,
                'resourcePageClass' => $editResourcePageClass,
            ]);
        }

        $this->components->info("Filament resource [{$resourcePath}] created successfully.");

        return static::SUCCESS;
    }

    /**
     * @return array<string, string>
     */
    protected function generateModel(string $model, string $modelNamespace, string $modelClass): array
    {
        $possibilities = ['Form', 'Table', 'Resource'];
        $params = [];

        if (in_array($model, $possibilities)) {
            $params['model'] = "{$modelNamespace}\\{$model} as {$model}Model";
            $params['modelClass'] = $model . 'Model';
        } else {
            $params['model'] = "{$modelNamespace}\\{$model}";
            $params['modelClass'] = $modelClass;
        }

        return $params;
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
}

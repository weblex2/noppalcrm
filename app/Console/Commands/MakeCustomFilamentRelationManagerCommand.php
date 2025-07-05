<?php

namespace App\Console\Commands;

use Filament\Facades\Filament;
use Filament\Panel;
use Filament\Support\Commands\Concerns\CanIndentStrings;
use Filament\Support\Commands\Concerns\CanManipulateFiles;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

#[AsCommand(name: 'make:custom-filament-relation-manager')]
class MakeCustomFilamentRelationManagerCommand extends Command
{
    use CanIndentStrings;
    use CanManipulateFiles;

    protected $description = 'Create a new custom Filament relation manager using a custom stub';

    protected $signature = 'make:custom-filament-relation-manager {resource?} {relationship?} {recordTitleAttribute?} {--attach} {--associate} {--soft-deletes} {--view} {--panel=} {--F|force}';

    protected function copyStubToApp(string $stubName, string $destination, array $replacements = []): int
{
    // Prüfe, ob $stubName bereits ein Pfad ist (enthält Slash oder Backslash)
    if (str_contains($stubName, '/') || str_contains($stubName, '\\')) {
        // Es sieht so aus, als wäre es schon ein Pfad - also direkt verwenden
        $stubPath = $stubName;
    } else {
        // Sonst aus Stub-Name einen Pfad zusammensetzen
        $stubPath = base_path("app/Filament/stubs/filament/{$stubName}.stub");
    }

    if (!file_exists($stubPath)) {
        $this->error("Stub-Datei nicht gefunden: {$stubPath}");
        return self::FAILURE;
    }

    $stubContent = file_get_contents($stubPath);

    // Ersetze Platzhalter im Stub
    foreach ($replacements as $key => $value) {
       #$stubContent = str_replace("{{{$key}}}", $value, $stubContent);
       $stubContent = preg_replace('/{{\s*' . preg_quote($key, '/') . '\s*}}/', $value, $stubContent);
    }

    $directory = dirname($destination);
    if (!is_dir($directory)) {
        mkdir($directory, 0755, true);
    }

    file_put_contents($destination, $stubContent);

    return self::SUCCESS;
}



    public function handle(): int
    {
        $resource = (string) str(
            $this->argument('resource') ?? text(
                label: 'What is the resource you would like to create this in?',
                placeholder: 'DepartmentResource',
                required: true,
            ),
        )
            ->studly()
            ->trim('/')->trim('\\')->trim(' ')
            ->replace('/', '\\');

        if (! str($resource)->endsWith('Resource')) {
            $resource .= 'Resource';
        }

        $relationship = (string) str($this->argument('relationship') ?? text(
            label: 'What is the relationship?',
            placeholder: 'members',
            required: true,
        ))->trim(' ');

        $managerClass = (string) str($relationship)->studly()->append('RelationManager');

        $recordTitleAttribute = (string) str($this->argument('recordTitleAttribute') ?? text(
            label: 'What is the title attribute?',
            placeholder: 'name',
            required: true,
        ))->trim(' ');

        $panel = $this->option('panel');

        if ($panel) {
            $panel = Filament::getPanel($panel, isStrict: false);
        }

        if (! $panel) {
            $panels = Filament::getPanels();

            $panel = (count($panels) > 1) ? $panels[select(
                label: 'Which panel would you like to create this in?',
                options: array_map(fn (Panel $panel): string => $panel->getId(), $panels),
                default: Filament::getDefaultPanel()->getId()
            )] : Arr::first($panels);
        }

        $resourceDirectories = $panel->getResourceDirectories();
        $resourceNamespaces = $panel->getResourceNamespaces();

        foreach ($resourceDirectories as $i => $dir) {
            if (str($dir)->startsWith(base_path('vendor'))) {
                unset($resourceDirectories[$i], $resourceNamespaces[$i]);
            }
        }

        $resourceNamespace = (count($resourceNamespaces) > 1)
            ? select(label: 'Which namespace would you like to create this in?', options: $resourceNamespaces)
            : (Arr::first($resourceNamespaces) ?? 'App\\Filament\\Resources');

        $resourcePath = (count($resourceDirectories) > 1)
            ? $resourceDirectories[array_search($resourceNamespace, $resourceNamespaces)]
            : (Arr::first($resourceDirectories) ?? app_path('Filament/Resources/'));

        $path = (string) str($managerClass)
            ->prepend("{$resourcePath}/{$resource}/RelationManagers/")
            ->replace('\\', '/')
            ->append('.php');

        if (! $this->option('force') && $this->checkForCollision([$path])) {
            return static::INVALID;
        }

        $tableHeaderActions = [
            'Tables\\Actions\\CreateAction::make(),'
        ];

        if ($this->option('associate')) $tableHeaderActions[] = 'Tables\\Actions\\AssociateAction::make(),';
        if ($this->option('attach')) $tableHeaderActions[] = 'Tables\\Actions\\AttachAction::make(),';

        $tableHeaderActions = implode(PHP_EOL, $tableHeaderActions);

        $tableActions = ['Tables\\Actions\\EditAction::make(),'];
        if ($this->option('view')) $tableActions[] = 'Tables\\Actions\\ViewAction::make(),';
        if ($this->option('associate')) $tableActions[] = 'Tables\\Actions\\DissociateAction::make(),';
        if ($this->option('attach')) $tableActions[] = 'Tables\\Actions\\DetachAction::make(),';
        $tableActions[] = 'Tables\\Actions\\DeleteAction::make(),';
        if ($this->option('soft-deletes')) {
            $tableActions[] = 'Tables\\Actions\\ForceDeleteAction::make(),';
            $tableActions[] = 'Tables\\Actions\\RestoreAction::make(),';
        }

        $tableActions = implode(PHP_EOL, $tableActions);

        $tableBulkActions = ['Tables\\Actions\\DeleteBulkAction::make(),'];
        if ($this->option('associate')) $tableBulkActions[] = 'Tables\\Actions\\DissociateBulkAction::make(),';
        if ($this->option('attach')) $tableBulkActions[] = 'Tables\\Actions\\DetachBulkAction::make(),';
        if ($this->option('soft-deletes')) {
            $tableBulkActions[] = 'Tables\\Actions\\ForceDeleteBulkAction::make(),';
            $tableBulkActions[] = 'Tables\\Actions\\RestoreBulkAction::make(),';
        }

        $tableBulkActions = implode(PHP_EOL, $tableBulkActions);

        $modifyQueryUsing = $this->option('soft-deletes')
            ? PHP_EOL . $this->indentString("->modifyQueryUsing(fn (Builder \$query) => \$query->withoutGlobalScopes([\n    SoftDeletingScope::class,\n]))", 3)
            : '';

        // <<< WICHTIG: Eigene Stub verwenden >>>
        $customStubPath = app_path('Filament/stubs/filament/ResourceManagePage.stub');

        if (!file_exists($customStubPath)) {
            $this->error("Stub-Datei nicht gefunden: {$customStubPath}");
            return static::FAILURE;
        }


        $this->copyStubToApp('RelationManager', $path, [
            'table' => strtolower(Str::plural(substr($resource,0,-8)))."::".strtolower(Str::plural($relationship)),
            'modifyQueryUsing' => $modifyQueryUsing,
            'namespace' => "{$resourceNamespace}\\{$resource}\\RelationManagers",
            'managerClass' => $managerClass,
            'recordTitleAttribute' => $recordTitleAttribute,
            'relationship' => strtolower(Str::plural($relationship)),
            'tableActions' => $this->indentString($tableActions, 4),
            'tableBulkActions' => $this->indentString($tableBulkActions, 5),
            'tableFilters' => $this->indentString(
                $this->option('soft-deletes') ? 'Tables\\Filters\\TrashedFilter::make()' : '//',
                4,
            ),
            'tableHeaderActions' => $this->indentString($tableHeaderActions, 4),
        ]);

        $this->components->info("Custom relation manager [{$path}] created successfully.");
        return static::SUCCESS;
    }
}

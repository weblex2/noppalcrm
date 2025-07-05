<?php

namespace App\Livewire;

use Filament\Forms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Livewire\Component;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use App\Http\Controllers\FilamentController;

class RelationManagerCreator extends Component implements HasForms
{
    use Forms\Concerns\InteractsWithForms;

    public array $formState = [];

    // Liste der vorhandenen Resources (für Select)
    public array $resources = [];

    public function mount(): void
    {
        $this->formState = [
            'resource' => null,
            'relation_name' => null,
            'record_title_attribute' => null,
        ];

        // Alle vorhandenen Filament Resources ermitteln (ohne Pages)
        $this->resources = collect(File::allFiles(app_path('Filament/Resources')))
            ->filter(fn ($file) =>
                $file->getExtension() === 'php'
                && str_ends_with($file->getFilename(), 'Resource.php')
                && !str_contains($file->getRelativePath(), 'Pages')
                && !str_contains($file->getRelativePath(), 'Widgets')
                && !str_contains($file->getRelativePath(), 'RelationManagers')
            )
            ->map(fn ($file) => 'App\\Filament\\Resources\\' . str_replace(
                ['/', '.php'],
                ['\\', ''],
                $file->getRelativePathname()
            ))
            ->filter(fn ($class) => class_exists($class))
            ->mapWithKeys(function ($class) {
                $basename = class_basename($class); // z. B. TestResource
                $name = str_replace('Resource', '', $basename); // z. B. Test
                $key = Str::kebab($name); // z. B. test, company-profile → company-profile
                return [$key => $name];
            })
            ->toArray();
    }

    public function getFormProperty(): Form
    {
        return $this->makeForm()
            ->schema([
                Select::make('resource')
                    ->label('Parent Resource')
                    ->options($this->resources)
                    ->required()
                    ->searchable(),
                    //->reactive(),
                Select::make('relation_name')
                    ->label('Relation')
                    ->options($this->resources)
                    ->required()
                    ->helperText('Name der Eloquent-Relation, z.B. "comments"'),
                TextInput::make('record_title_attribute')
                    ->label('Record Title Attribute')
                    ->helperText('Optional: Attributname, z.B. "title" für Listeneinträge'),
            ])
            ->statePath('formState');
    }

    public function createRelationManager(): void
    {
        $data = $this->formState;
        $resourceClass = $data['resource'];
        $relationName = Str::camel($data['relation_name']);
        $recordTitle = $data['record_title_attribute'] ?? null;

        // Pfad RelationManager-Klasse
        $resourcePath = Str::studly(str_replace('\\', DIRECTORY_SEPARATOR, $resourceClass))."Resource";
        $relationManagerDir = app_path("Filament/Resources/{$resourcePath}/RelationManagers");
        $relationManagerClass = Str::studly($relationName) . 'RelationManager';
        $relationManagerPath = $relationManagerDir . DIRECTORY_SEPARATOR . $relationManagerClass . '.php';

        if (file_exists($relationManagerPath)) {
            session()->flash('error', "RelationManager {$relationManagerClass} existiert bereits.");
            return;
        }
        // Call the Artisan Command
        $res = Artisan::call('make:custom-filament-relation-manager '.$resourcePath.' '. $relationName.' '.$recordTitle);
        $output = Artisan::output();

        // Create Relation
        $config['source'] = Str::studly(str_replace('\\', DIRECTORY_SEPARATOR, $resourceClass));
        $config['target'] = Str::studly(Str::plural($relationName));
        $config['method'] = 'HasMany';
        $config['field'] = 'id';
        $config['relation_name'] = strtolower(Str::plural($relationName));

        // Controller-Methode aufrufen
        $exists = app(FilamentController::class)->checkIfRelationExists($config);

        if ($res==0){
            session()->flash('success', "RelationManager {$relationManagerClass} erfolgreich erstellt.");
            Notification::make()
                ->title('RelationManager erstellt')
                ->success()
                ->body("RelationManager {$relationManagerClass} wurde erfolgreich erstellt.")
                ->send();

            // Formular zurücksetzen
            $this->formState = [];
        }
        else{
            session()->flash('error', "RelationManager {$relationManagerClass} konnte nicht erstellt werden.");
            Notification::make()
                ->title('Fehler beim Erstellen des RelationManagers')
                ->error()
                ->body(trim($output))
                ->send();
        }
    }


    public function render()
    {
        return view('livewire.relation-manager-creator');
    }
}

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



class ResourceCreator extends Component implements HasForms
{
    use Forms\Concerns\InteractsWithForms;

    public array $formState = [];
    public array $navigationGroups = [];

    public function mount(): void
    {
        Log::info('ResourceCreator: Mount aufgerufen', [
            'class' => __CLASS__,
            'file' => __FILE__,
            'time' => now()->toDateTimeString(),
            'livewire_id' => $this->getId(),
        ]);

        try {
            $this->navigationGroups = \DB::table('filament_configs')
                ->distinct()
                ->where('type','=','naviagtion_group')
                ->pluck('value')
                ->filter()
                ->values()
                ->toArray();
            Log::info('ResourceCreator: Navigation Groups geladen', [
                'navigationGroups' => $this->navigationGroups,
            ]);
        } catch (\Throwable $e) {
            Log::error('ResourceCreator: Fehler beim Laden der Navigation Groups', [
                'error' => $e->getMessage(),
            ]);
        }

        $this->formState = [];
    }

    public function getFormProperty(): Form
    {
        Log::info('ResourceCreator: getFormProperty aufgerufen', [
            'time' => now()->toDateTimeString(),
        ]);

        return $this->makeForm()
            ->schema([
                TextInput::make('resourceName')
                    ->label('Ressourcenname')
                    ->required()
                    ->placeholder('z. B. User')
                    ->rules(['string', 'max:255']),
                Select::make('navigation_group')
                    ->label('Navigationsgruppe')
                    ->options(array_combine($this->navigationGroups, $this->navigationGroups))
                    ->placeholder('Wähle eine Gruppe')
                    ->options(
                        fn () => \DB::table('resources')
                            ->distinct()
                            ->pluck('navigation_group')
                            ->filter() // entfernt leere/null Werte
                            ->mapWithKeys(fn ($value) => [$value => $value])
                            ->toArray()
                    ),
            ])
            ->statePath('formState');
    }

    public function createResource(): void
    {
        $form = $this->getFormProperty();
        $data = $form->getState();

        $resourceName = Str::studly($data['resourceName']) . 'Resource';
        $navigationGroup = $data['navigation_group'];

        try {

            $status = Artisan::call('make:custom-filament-resource', [
                'name' => $resourceName,
                '--model' => true,
                '--migration' => true,
                '--generate' => true,
                '--force' => true,
            ]);

            $output = Artisan::output();



            if ($status === 0) {
                $this->addTraitToModel($resourceName);
                session()->flash('success', "✅ Ressource erfolgreich erstellt:\n<pre>$output</pre>");
                Notification::make()
                ->title("Neue Resource")
                ->success()
                ->body("Die neue Resource wurde erfolgreich erstellt.")
                ->send();
                $this->formState = [];
                Log::channel('crm')->info('ResourceCreator: Ressource erstellt', [
                    'resourceName' => $resourceName,
                    'output' => $output,
                ]);

                $status = Artisan::call('shield:generate --all --panel=admin');
                if ($status === 0){
                    session()->flash('success', "✅ Policies erfolgreich erstellt:\n<pre>$output</pre>");
                    Notification::make()
                        ->title("Policy Refresh")
                        ->success()
                        ->body("Die Policies wurden erfolgreich refreshed.")
                        ->send();
                }

            } else {
                session()->flash('error', "❌ Fehler beim Erstellen der Ressource:\n<pre>$output</pre>");
                Log::error('ResourceCreator: Fehler beim Erstellen der Ressource', [
                    'output' => $output,
                ]);
            }
        } catch (\Throwable $e) {
            session()->flash('error', '⚠️ Fehler: ' . $e->getMessage());
            Log::error('ResourceCreator: Ausnahme beim Erstellen der Ressource', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        } finally {
            // We don't want to keep the migration file
            $this->removeMigrationFile($resourceName);
        }

    }



    public function render()
    {
        Log::info('ResourceCreator: Render aufgerufen', [
            'time' => now()->toDateTimeString(),
            'livewire_id' => $this->getId(),
        ]);
        return view('livewire.resource-creator');
    }

    private function removeMigrationFile($resourceName){
        // Migration-Datei finden
        $migrationFiles = File::files(database_path('migrations'));
        $latestMigration = collect($migrationFiles)
            ->sortByDesc(fn ($file) => $file->getMTime())
            ->first();
        $tableName = Str::plural(strtolower(trim($resourceName,'Resource')));
        // Migration löschen, wenn sie wirklich neu ist
        if ($latestMigration && str_contains($latestMigration->getFilename(), strtolower($tableName))) {
            $res = File::delete($latestMigration->getPathname());
        }
    }

    private function addTraitToModel($resourceName){
        $baseName = Str::studly(class_basename(trim($resourceName,'Resource')));
        $modelClass = 'App\\Models\\' . $baseName;
        // check if Trait already exists
        if (!file_exists("app\\Traits\\".$baseName."Relations.php")){
            $path = app_path("Traits/{$baseName}Relations.php");
            $content = file_get_contents(app_path("Filament/stubs/filament/relations/traitContent.stub"));
            $content = str_replace("{{Model}}",$baseName,$content);
            // Ordner erstellen, falls er nicht existiert
            if (!File::exists(dirname($path))) {
                File::makeDirectory(dirname($path), 0755, true);
            }
            File::put($path, $content);
        }
    }
}

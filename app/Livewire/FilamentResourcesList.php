<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Filament\Notifications\Notification;
use App\Models\ResourceConfig;

class FilamentResourcesList extends Component
{
    public $resources = [];
    public $deleteTable = false;
    public $commandOutput = '';
     // Array der Resources, die ausgeschlossen werden sollen (Klassennamen)
    protected array $excludedResources = [
        'App\Filament\Resources\TableFieldsResource',
        // ... weitere ausschließen
    ];

    public function mount()
    {
        $resourceNamespace = 'App\\Filament\\Resources';
        $resourcePath = app_path('Filament/Resources');

        $files = scandir($resourcePath);
        $resources = [];

        foreach ($files as $file) {
            if (!is_string($file)) {
                continue; // Überspringen wenn kein String
            }

            if ($file === '.' || $file === '..') {
                continue; // Überspringen der Ordner-Verweise
            }

            if (Str::endsWith($file, 'Resource.php')) {

                $class = $resourceNamespace . '\\' . pathinfo($file, PATHINFO_FILENAME);

                 // Überspringen wenn in excludedResources
                if (in_array($class, $this->excludedResources)) {
                    continue;
                }

                if (class_exists($class)) {
                    $resourceInstance = new $class();

                    if (method_exists($resourceInstance, 'getModel')) {
                        $resources[] = [
                            'class' => $class,
                            'model' => $resourceInstance->getModel(),
                        ];
                    }
                }
            }
        }


        $this->resources = $resources;

    }

    public function nukeResource($resourceName)
    {
        $resourceName = substr($resourceName,0,-8);
        // Artisan Command ausführen (bitte deinen Command-Namen anpassen)
        $status = Artisan::call('nuke:resource', [
            'name' => $resourceName,
            '--force-db' => true,
        ]);

        $output = $this->commandOutput = Artisan::output();
        if ($status === 0) {
            // Erfolg
            #session()->flash('message', "Resource {$resourceName} wurde erfolgreich gelöscht.");
            Notification::make()
                ->title("Resource {$resourceName} gelöscht")
                ->success()
                ->body("Die Resource wurde erfolgreich entfernt.")
                ->send();
            return redirect()->route('filament.admin.pages.setup');
        } else {
            // Fehler
            session()->flash('error', "Fehler beim Löschen der Resource {$resourceName}.<br>".nl2br($output));
        }
        session()->flash('message', "Resource {$resourceName} wurde gelöscht.");
        session()->flash('message', );

        // Liste aktualisieren
        $this->mount();
    }

    public function rebuildResource($resourceName){
        $path = app_path('Filament/Resources/').$resourceName.".php";
        $label = Str::of($resourceName)->plural()->before('Resource')->replaceMatches('/([a-z])([A-Z])/', '$1 $2');
        \DB::table('resource_configs')->insertOrIgnore([
            'resource' => $resourceName,
            'navigation_group' => '',
            'navigation_label' => $label,
            'navigation_icon' => 'heroicon-o-rectangle-stack',
            'created_at' => now(),
            'updated_at' => now(),
            ]);

        $status = Artisan::call('make:custom-filament-resource', [
            'name' => $resourceName,
            '--generate' => true,
            '--force' => true,
        ]);



        Notification::make()
                ->title("Resource successfully rebuilt")
                ->success()
                ->body("Die Resource wurde erfolgreich neu erstellt.")
                ->send();

    }

    public function render()
    {
        return view('livewire.filament-resources-list');
    }


}

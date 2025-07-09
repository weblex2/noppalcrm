<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Filament\Notifications\Notification;

class FilamentRelationManagerList extends Component
{
    public $relationManagers = [];
    public $deleteTable = false;
    public $commandOutput = '';
    public $relationName = '';

    protected array $excludedRelationManagers = [
        // ... weitere ausschließen
    ];

    public function mount()
    {
        $baseNamespace = 'App\\Filament\\Resources';
        $basePath = app_path('Filament/Resources');

        $managers = [];

        // Alle Unterordner in Resources durchgehen
        foreach (scandir($basePath) as $resourceFolder) {
            $resourceFolderPath = $basePath . DIRECTORY_SEPARATOR . $resourceFolder;

            if (!is_dir($resourceFolderPath) || in_array($resourceFolder, ['.', '..'])) {
                continue;
            }

            $relationManagersPath = $resourceFolderPath . DIRECTORY_SEPARATOR . 'RelationManagers';

            if (!is_dir($relationManagersPath)) {
                continue;
            }

            foreach (scandir($relationManagersPath) as $file) {
                if (!Str::endsWith($file, 'RelationManager.php')) {
                    continue;
                }

                $class = $baseNamespace . '\\' . $resourceFolder . '\\RelationManagers\\' . pathinfo($file, PATHINFO_FILENAME);

                if (in_array($class, $this->excludedRelationManagers)) {
                    continue;
                }

                if (class_exists($class)) {
                    $managers[] = [
                        'class' => $class,
                        'resource' => $resourceFolder,
                        'name' => class_basename($class),
                    ];
                }
            }
        }

        $this->relationManagers = $managers;
    }

    public function deleteRelationManager($className,$relationName)
    {
        $resource = Str::before(class_basename($relationName), 'Resource');
        $relation = Str::of($className)->before('RelationManager')->lower()->toString();

        $status = Artisan::call('delete:relation-manager', [
            'resource' => $resource,
            'relation' => $relation,
        ]);

        $output = $this->commandOutput = Artisan::output();

        if ($status === 0) {
            Notification::make()
                ->title("RelationManager gelöscht")
                ->success()
                ->body("Der RelationManager {$relationName} -> {$className} wurde erfolgreich entfernt.")
                ->send();

            return redirect()->route('filament.admin.pages.setup');
        } else {
            session()->flash('error', "Fehler beim Löschen von {$relationName}.<br>" . nl2br($output));
        }

        $this->mount(); // Liste neu laden
    }

    public function render()
    {
        return view('livewire.filament-relation-manager-list');
    }


}

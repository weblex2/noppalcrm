<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class NukeResource extends Command
{
    protected $signature = 'nuke:resource {name} {--f|force-db}';
    protected $description = 'Löscht Model, Migration, Resource, Views und Tabelle einer Laravel-Resource';

    public function handle()
    {
        $name = Str::studly($this->argument('name'));
        $lower = Str::snake($name);
        $plural = Str::plural($lower);

        $paths = [
            app_path("Models/{$name}.php"),
            app_path("Filament/Resources/{$name}Resource.php"),
            app_path("Filament/Resources/{$name}Resource/Pages"),
            resource_path("views/{$plural}"),
            //database_path("migrations"),
        ];

        // Lösche die Dateien/Ordner
        foreach ($paths as $path) {
            if (is_dir($path)) {
                File::deleteDirectory($path);
                $this->info("Ordner gelöscht: $path");
            } elseif (is_file($path)) {
                File::delete($path);
                $this->info("Datei gelöscht: $path");
            } elseif (Str::contains($path, 'migrations')) {
                // Migrationen suchen und löschen
                $deleted = false;
                foreach (File::files($path) as $file) {
                    if (Str::contains($file->getFilename(), $plural)) {
                        File::delete($file->getPathname());
                        $this->info("Migration gelöscht: " . $file->getFilename());
                        $deleted = true;
                    }
                }
                if (!$deleted) {
                    $this->warn("Keine Migrationen mit Bezug zu '{$plural}' gefunden.");
                }
            }
        }

        // Optional: Tabelle aus DB löschen
        if ($this->option('force-db')) {
            \DB::statement("DROP TABLE IF EXISTS {$plural}");
            $this->info("Tabelle '{$plural}' automatisch gelöscht (--force-db).");
        } elseif ($this->confirm("Möchtest du die Tabelle '{$plural}' auch aus der DB löschen?", false)) {
            \DB::statement("DROP TABLE IF EXISTS {$plural}");
            $this->info("Tabelle '{$plural}' gelöscht.");
        }

        $this->info("Ressource '{$name}' erfolgreich entfernt.");
    }
}

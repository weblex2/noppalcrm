<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class NukeResource extends Command
{
    protected $signature = 'nuke:resource {name} {--f|force-db}';
    protected $description = 'Löscht Model, Migration, Resource, Views und Tabelle einer Laravel-Resource';

    public function handle()
    {
        $name = Str::studly($this->argument('name'));
        $lower = Str::snake($name);
        $plural = Str::plural($lower);
        $resourceName = "{$name}Resource";

        $paths = [
            app_path("Models/{$name}.php"),
            app_path("Filament/Resources/{$name}Resource.php"),
            app_path("Filament/Resources/{$name}Resource/Pages"),
            app_path("Traits/{$name}Relations.php"),
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

             // Einträge aus table_fields löschen
            $deletedFields = \DB::table('table_fields')
                ->where('table', $resourceName)
                ->delete();

            $this->info("{$deletedFields} Einträge in 'table_fields' gelöscht.");
            \Log::info("{$deletedFields} Einträge in 'table_fields' gelöscht.");


             // Einträge aus table_fields löschen
            $deletedFields = \DB::table('filament_configs')
                ->where('resource', $resourceName)
                ->delete();

            $this->info("{$deletedFields} Einträge in 'filament_configs' gelöscht.");
            \Log::info("{$deletedFields} Einträge in 'filament_configs' gelöscht.");

        } elseif ($this->confirm("Möchtest du die Tabelle '{$plural}' auch aus der DB löschen?", false)) {
            \DB::statement("DROP TABLE IF EXISTS {$plural}");
            $this->info("Tabelle '{$plural}' gelöscht.");

            // Einträge aus table_fields löschen
            $deletedFields = \DB::table('table_fields')
                ->where('table', $plural)
                ->delete();

            $this->info("{$deletedFields} Einträge in 'table_fields' gelöscht.");
        }

        $this->info("Ressource '{$name}' erfolgreich entfernt.");
    }
}

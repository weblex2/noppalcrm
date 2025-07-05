<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class DeleteRelationManager extends Command
{
    protected $signature = 'delete:relation-manager {resource} {relation}';
    protected $description = 'Löscht einen RelationManager in einer Resource-Klasse';

    public function handle()
    {
        $resource = $this->argument('resource');
        $relation = $this->argument('relation');

        $resourceClass = Str::studly($resource) . 'Resource';
        $relationClass = Str::studly($relation) . 'RelationManager';

        $path = base_path("app/Filament/Resources/{$resourceClass}/RelationManagers/{$relationClass}.php");

        if (!file_exists($path)) {
            $this->error("❌ RelationManager-Datei nicht gefunden: {$path}");
            return Command::FAILURE;
        }

        unlink($path);

        $this->info("✅ RelationManager gelöscht: {$relationClass} in Resource {$resourceClass}");
        return Command::SUCCESS;
    }
}

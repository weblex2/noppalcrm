<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redirect;
use Filament\Notifications\Notification;

class LogViewer extends Page
{
    protected static string $view = 'filament.pages.log-viewer';


    public static function getNavigationLabel(): string
    {
        $resourceName = "Page::".class_basename(static::class); // z. B. ResourceConfigResource
        return \App\Models\ResourceConfig::where('resource', $resourceName)->value('navigation_label') ?? "Logs";
    }

    public static function getNavigationGroup(): ?string
    {
        $resourceName = "Page::".class_basename(static::class); // ergibt z. B. "HouseResource"
        return \App\Models\ResourceConfig::where('resource', $resourceName)->value('navigation_group') ?? "Configuration";
    }

    public static function getNavigationIcon(): ?string
    {
        $resourceName = "Page::".class_basename(static::class); // ergibt z. B. "HouseResource"
        return \App\Models\ResourceConfig::where('resource', $resourceName)->value('navigation_icon') ?? 'heroicon-o-document-text';
    }

    public array $logs = [];

    public function mount(): void
    {
        $this->loadLogs();
          $user = auth()->user();

    }

    public function loadLogs(): void
    {
        $logPath = storage_path('logs');
        $user = auth()->user();
        #dump($user->hasPermissionTo('view_any_role'));
        #dd($user->hasPermissionTo('view permissions'));

        $files = collect(File::files($logPath))
            ->filter(fn ($file) => $file->getExtension() === 'log')
            ->sortByDesc(fn ($file) => $file->getMTime());

        $this->logs = $files->map(fn ($file) => [
            'name' => $file->getFilename(),
            'content' => File::get($file->getRealPath()),
        ])->values()->all();
    }

    public function deleteLog(string $filename): void
    {
        $path = storage_path('logs/' . $filename);
        if (File::exists($path)) {
            File::put($path, '');;
            Notification::make()
                ->title("Log '$filename' wurde gelöscht.")
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title("Datei '$filename' nicht gefunden.")
                ->warning()
                ->send();
        }

        $this->loadLogs(); // Refresh logs
    }
}

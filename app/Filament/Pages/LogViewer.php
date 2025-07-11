<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redirect;
use Filament\Notifications\Notification;

class LogViewer extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.pages.log-viewer';
    protected static ?string $navigationGroup = 'Configuration';
    protected static ?string $navigationLabel = 'Logs';
    public array $logs = [];

    public function mount(): void
    {
        $this->loadLogs();
    }

    public function loadLogs(): void
    {
        $logPath = storage_path('logs');

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

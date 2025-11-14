<?php

namespace App\Filament\Resources\DummyResource\Pages;

use App\Filament\Resources\DummyResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;

class CreateDummy extends CreateRecord
{
    protected static string $resource = DummyResource::class;

    public ?string $resourceName = null; // Instanzproperty

    public function mount(): void
    {
        parent::mount();

        // Default ResourceName, falls nichts übergeben wird
        $this->resourceName = $this->resourceName ?? 'DummyResource';
    }

     // Livewire Hook: wenn resourceName von außen geändert wird
    public function updatedResourceName($value): void
    {
        $this->resourceName = $value;
    }

    public function getTitle(): Htmlable|string
    {
        return $this->resourceName ? "{$this->resourceName}" : parent::getTitle();
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    // Buttons deaktivieren
    protected function getFormActions(): array
    {
        $actions = parent::getFormActions();

        foreach ($actions as $key => $action) {
            $actions[$key] = $action->disabled(); // alle Actions deaktivieren
        }

        return $actions;
    }

    
}

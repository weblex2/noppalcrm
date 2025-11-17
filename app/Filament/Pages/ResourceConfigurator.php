<?php

namespace App\Filament\Pages;

use App\Http\Controllers\FilamentController;
use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

class ResourceConfigurator extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog';
    protected static ?string $navigationLabel = 'Resource Configurator';
    protected static string $view = 'filament.pages.resource-configurator';


    public ?string $selectedResource = null;
    public string $customResource = '';
    public ?string $activeResource = null;
    public array $availableResources = [];
    public bool $isLoading = false;

    public function mount(): void
    {
        $this->availableResources = FilamentController::getResourcesDropdown(false,false,false);
    }

    protected function getFormSchema(): array
    {
        return [
            Select::make('selectedResource')
                ->label('Select Resource')
                ->searchable()
                ->options($this->availableResources)
                ->getSearchResultsUsing(function (string $search) {
                    // Statische Liste laden
                    $tags = [];

                    // Den eingegebenen Suchbegriff prüfen
                    if ($search && !array_key_exists($search, $tags)) {
                        // Neuen Wert temporär hinzufügen
                        $tags[$search] = $search;
                    }

                    return $tags;
                })
                ->getOptionLabelUsing(function ($value) {
                    return $value ?: '';
                })
                ->afterStateUpdated(function ($state, callable $set) {
                    // Wenn ein neuer Wert eingegeben wurde, diesen direkt setzen
                    if ($state && !in_array($state, ['configuration', 'test'])) {
                        $this->isLoading = true;
                        $set('selectedResource', $state);  // Dropdown-Wert setzen
                        $this->activeResource = $state;
                        $this->isLoading = false;
                    }
                })
                ->reactive(),
            ];
    }

    public function createResource()
    {
        $new = $this->selectedResource;

        if ($new && !in_array($new, $this->availableResources)) {
            $this->availableResources[$new] = $new;
            $fc = new FilamentController();
            $res = $fc->createResource($new);
            // Dummy Resource erst anzeigen, wenn createResource fertig ist
            if ($res == true){
                $this->activeResource = $new;
                /* Notification::make()
                    ->title('Resource erstellt')
                    ->success()
                    ->body("Die Resource '$new' wurde erfolgreich erstellt.")
                    ->send(); */
            }
            else{
                Notification::make()
                    ->title('Resource nicht erstellt')
                    ->danger()
                    ->body("Die Resource '$new' konnte nicht erstellt werden.")
                    ->send();
            }
        }
    }

    public function getActiveResource(): ?string
    {
        return $this->activeResource ?: null;
    }
}

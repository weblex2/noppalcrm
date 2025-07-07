<?php

namespace App\Filament\Resources\TableFieldsResource\Pages;

use App\Filament\Resources\TableFieldsResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

class CreateTableFields extends CreateRecord
{
    protected static string $resource = TableFieldsResource::class;

    public function mount(): void
    {
        // Wenn duplicate_data vorhanden sind, fülle die Form mit diesen Daten
        if ($duplicateData = request()->query('duplicate_data')) {
            $this->form->fill(json_decode($duplicateData, true));
        } else {
            // Fülle die Form mit den Standardwerten aus dem Formularschema
            $this->form->fill();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getFormData(): array
    {
        $duplicateData = request()->query('duplicate_data');
        if ($duplicateData) {
            return json_decode($duplicateData, true);
        }
        // Hole die Standardwerte aus dem Formularschema
        $form = $this->getForm();
        $schema = $form->getSchema();
        $defaultData = [];

        foreach ($schema->getComponents() as $component) {
            // Rekursiv durch Gruppen und Sektionen iterieren
            if ($component instanceof \Filament\Forms\Components\Group || $component instanceof \Filament\Forms\Components\Section) {
                foreach ($component->getChildComponents() as $childComponent) {
                    $defaultData[$childComponent->getName()] = $childComponent->getDefaultState();
                }
            } else {
                $defaultData[$component->getName()] = $component->getDefaultState();
            }
        }

        return $defaultData;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        \Log::channel('crm')->info($data);
        $exists = \App\Models\TableFields::where('form', $data['form'])
            ->where('table', $data['table'])
            ->where('field', $data['field'])
            ->exists();

        if ($exists) {
            Notification::make()
                ->title('Duplikat gefunden')
                ->body('Ein Eintrag mit diesen Werten existiert bereits.')
                ->danger()
                ->send();
            throw ValidationException::withMessages([
                'data.form' => 'Ein Eintrag mit diesen Werten existiert bereits.',
                'data.table' => 'Ein Eintrag mit diesen Werten existiert bereits.',
                'data.field' => 'Ein Eintrag mit diesen Werten existiert bereits.',
            ]);
        }

        return $data;
    }
}

<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        \Log::channel('crm')->info('MutateFormDataBeforeSave', ['data' => $data]);
        if (!filled($data['user01'] ?? null)) {
            unset($data['user01']); // bleibt alter DB-Wert erhalten
        }

        return $data;
    }
}

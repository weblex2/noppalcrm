<?php

namespace App\Filament\Resources\FilamentConfigResource\Pages;

use App\Filament\Resources\FilamentConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFilamentConfig extends EditRecord
{
    protected static string $resource = FilamentConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

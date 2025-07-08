<?php

namespace App\Filament\Resources\ResourceConfigResource\Pages;

use App\Filament\Resources\ResourceConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditResourceConfig extends EditRecord
{
    protected static string $resource = ResourceConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

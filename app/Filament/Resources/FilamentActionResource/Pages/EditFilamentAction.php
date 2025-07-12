<?php

namespace App\Filament\Resources\FilamentActionResource\Pages;

use App\Filament\Resources\FilamentActionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFilamentAction extends EditRecord
{
    protected static string $resource = FilamentActionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

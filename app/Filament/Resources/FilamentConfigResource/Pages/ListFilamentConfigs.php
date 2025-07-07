<?php

namespace App\Filament\Resources\FilamentConfigResource\Pages;

use App\Filament\Resources\FilamentConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFilamentConfigs extends ListRecords
{
    protected static string $resource = FilamentConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

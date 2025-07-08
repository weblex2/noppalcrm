<?php

namespace App\Filament\Resources\ResourceConfigResource\Pages;

use App\Filament\Resources\ResourceConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListResourceConfigs extends ListRecords
{
    protected static string $resource = ResourceConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

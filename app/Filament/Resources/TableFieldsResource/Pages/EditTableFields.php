<?php

namespace App\Filament\Resources\TableFieldsResource\Pages;

use App\Filament\Resources\TableFieldsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Facades\Filament;


class EditTableFields extends EditRecord
{
    protected static string $resource = TableFieldsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

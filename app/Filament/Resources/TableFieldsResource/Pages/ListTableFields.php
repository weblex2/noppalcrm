<?php

namespace App\Filament\Resources\TableFieldsResource\Pages;

use App\Filament\Resources\TableFieldsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Asmit\ResizedColumn\HasResizableColumn;

class ListTableFields extends ListRecords
{
    use HasResizableColumn;
    protected static string $resource = TableFieldsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

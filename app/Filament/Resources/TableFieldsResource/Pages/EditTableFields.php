<?php

namespace App\Filament\Resources\TableFieldsResource\Pages;

use App\Filament\Resources\TableFieldsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Facades\Filament;
use App\Http\Controllers\FilamentController;


class EditTableFields extends EditRecord
{
    public array $tableFilters = [];
    public string $tableSearch = '';
    public ?string $tableSortColumn = null;
    public ?string $tableSortDirection = null;
    public int $tablePage = 1;
    protected static string $resource = TableFieldsResource::class;


    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        \Log::channel('crm')->info($data);
        if ($data['type'] === 'relation') {
            $config['source'] = $data['table'];
            $config['target'] = $data['relation_table'];
            $config['method'] = 'BelongsTo';
            $config['field'] = $data['field'];
            $config['relation_name'] = $data['relation_table'];

            // Controller-Methode aufrufen
            $exists = app(FilamentController::class)->checkIfRelationExists($config);
        }

        return $data;
    }



    protected function getRedirectUrl(): string
    {
        return TableFieldsResource::getUrl('index', []);
    }
}

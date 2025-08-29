<?php

namespace App\Filament\Resources\FilamentConfigResource\Pages;

use App\Filament\Resources\FilamentConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Http\Controllers\FilamentController;
use Illuminate\Support\Str;

class EditFilamentConfig extends EditRecord
{
    protected static string $resource = FilamentConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return FilamentConfigResource::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        \Log::channel('crm')->info($data);
        if ($data['is_repeater'] == true) {
            $config['source'] =  Str::of($data['resource'])->replaceLast('Resource', '')->snake()->lower()->value();
            $config['target'] = Str::of($data['repeats_resource'])->replaceLast('Resource', '')->snake()->lower()->value();
            $config['method'] = 'HasMany';
            $config['field'] = Str::of($data['resource'])->beforeLast('Resource')->lower()->value()."_id" ;
            $config['relation_name'] = Str::of($data['repeats_resource'])->replaceLast('Resource', '')->snake()->lower()->plural()->value();

            // Controller-Methode aufrufen
            $fc = new FilamentController();
            $exists = $fc->checkIfRelationExists($config);
        }

        return $data;
    }

}

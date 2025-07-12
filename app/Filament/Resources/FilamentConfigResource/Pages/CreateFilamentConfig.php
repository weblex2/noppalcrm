<?php

namespace App\Filament\Resources\FilamentConfigResource\Pages;

use App\Filament\Resources\FilamentConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateFilamentConfig extends CreateRecord
{
    protected static string $resource = FilamentConfigResource::class;

    protected function getRedirectUrl(): string
    {
        return FilamentConfigResource::getUrl('index');
    }
}

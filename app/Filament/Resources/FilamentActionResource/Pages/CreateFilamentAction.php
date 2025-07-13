<?php

namespace App\Filament\Resources\FilamentActionResource\Pages;

use App\Filament\Resources\FilamentActionResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateFilamentAction extends CreateRecord
{
    protected static string $resource = FilamentActionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

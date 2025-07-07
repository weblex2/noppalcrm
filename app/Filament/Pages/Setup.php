<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Resource;

class Setup extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static function getNavigationGroup(): ?string
    {
        return 'Configuration'; 
    }

    protected static string $view = 'filament.pages.setup';
}

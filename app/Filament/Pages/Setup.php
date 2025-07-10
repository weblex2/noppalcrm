<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Resource;

class Setup extends Page
{

    public static function getNavigationLabel(): string 
    {
        $resourceName = class_basename(static::class); // z. B. ResourceConfigResource
        return \App\Models\ResourceConfig::where('resource', $resourceName)->value('navigation_label') ?? "Setup";
    }

    public static function getNavigationGroup(): ?string
    {
        $resourceName = class_basename(static::class); // ergibt z. B. "HouseResource"
        return \App\Models\ResourceConfig::where('resource', $resourceName)->value('navigation_group') ?? "Configuration";
    }

    public static function getNavigationIcon(): ?string
    {
        $resourceName = class_basename(static::class); // ergibt z. B. "HouseResource"
        return \App\Models\ResourceConfig::where('resource', $resourceName)->value('navigation_icon') ?? 'heroicon-o-document-text';
    }


    protected static string $view = 'filament.pages.setup';
}

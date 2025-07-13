<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Filament\Widgets;
use Filament\Navigation\NavigationItem;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Asmit\ResizedColumn\ResizedColumnPlugin;
use App\Models\FilamentConfig;
use Illuminate\Support\Str;
use App\Providers\Filament\Resource;


class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {

        FilamentColor::register([
            'color1' => Color::hex('#0ea5e9'),
            'color2' => Color::hex('#dc2626'),
            'color3' => Color::hex('#16a34a'),
        ]);

        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->font('Poppins')
            ->maxContentWidth('1280px')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->navigationItems(
                $this->getNavigationLinks(),
            )
            ->brandName(
                \Illuminate\Support\Facades\Schema::hasTable('general_settings')
                    ? \App\Models\GeneralSetting::where('field', 'site_name')->value('value') ?? 'CRM'
                    : 'CRM'
            )
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make(),
                \BezhanSalleh\FilamentShield\FilamentShieldPlugin::make(),
                ResizedColumnPlugin::make()
                ->preserveOnDB() // Enable database storage (optional)
            ]);
    }

    public static function getNavigationLinks(){
        $filters = [];
        $filters = FilamentConfig::where('type','navlink')->orderBy('order', 'asc')->get();
        $navItems = [];
        foreach ($filters as $i => $filter){
            $name = ucfirst($filter->value);
            $resourceName = Str::studly($filter['resource'])."Resource";
            $filterKey = $filter->key;
            $resourceClass = "App\\Filament\\Resources\\{$resourceName}";

            if (class_exists($resourceClass)) {
                $navigation_group = $filter['navigation_group'];
                $navigation_icon = $filter['icon'] ?? 'heroicon-o-rectangle-stack';
                $navigation_label = $filter['label'] ?? Str::studly($filter['resource']) ." -> ".$filter->value;
                $navItem = NavigationItem::make($name);
                $navItem->url(function () use ($resourceClass, $filterKey, $filter): string {
                    if (
                        class_exists($resourceClass)
                        && method_exists($resourceClass, 'getUrl')
                    ) {
                        return $resourceClass::getUrl('index', [
                            'tableFilters' => [
                                $filter['field'] => ['value' => $filterKey],
                            ],
                        ]);
                    }

                    return '#'; // Kein valider Link → kein Fehler in Navigation
                })
                ->icon($navigation_icon)
                ->label($navigation_label)
                ->group($navigation_group);
                //->badge($counts[$filter] ?? 0);
                $navItems[] = $navItem;
            }
            else{
                \Log::channel('crm')->info('Error in Navlinks: Resource '. $resourceClass ." does not exist!");
            }
        }
        return $navItems;
    }

}

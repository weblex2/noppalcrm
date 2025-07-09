<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ResourceConfigResource\Pages;
use App\Filament\Resources\ResourceConfigResource\RelationManagers;
use App\Models\ResourceConfig;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Http\Controllers\FilamentFieldsController;

class ResourceConfigResource extends Resource
{
    public static $navigationGroups = ['PHP', 'Laravel', 'Livewire', 'Filament'];
    public $selectedGroup;
    protected static ?string $model = ResourceConfig::class;


    public static function getNavigationLabel(): string
    {
        $resourceName = class_basename(static::class); // z. B. ResourceConfigResource
        return \App\Models\ResourceConfig::where('resource', $resourceName)->value('navigation_label')
            ?? parent::getNavigationLabel(); // Fallback auf Standardlabel
    }

    public static function getNavigationIcon(): ?string{
        $resourceName = class_basename(static::class); // ergibt z. B. "HouseResource"
        return \App\Models\ResourceConfig::where('resource', $resourceName)->value('navigation_icon') ?? null;
    }

    public static function getNavigationGroup(): ?string
    {
        $resourceName = class_basename(static::class); // ergibt z. B. "HouseResource"
        return \App\Models\ResourceConfig::where('resource', $resourceName)->value('navigation_group') ?? null;
    }

    public static function form(Form $form): Form
    {
        $fc = new FilamentFieldsController('resource_configs',1);
        $schema = $fc->getSchema() ?? [];
        return $form->schema([
            Forms\Components\Select::make('resource')
                            ->label('Tabelle')
                            ->options(function () {
                                return array_filter(self::getTableOptions(), fn($label) => $label !== null && $label !== '');
                            })
                            ->required()
                            ->reactive() // wichtig für Reaktivität

                            /* ->dehydrateStateUsing(function ($state) {
                                // Optional: Speichere in snake_case
                                \Log::info('Dehydrate State:', ['state' => $state]);
                                \Log::info('Dehydrate State:', ['state' => Str::of($state)->snake()]);
                                #return Str::of($state)->studly();
                                return Str::of($state)->studly()."Resource";
                            }) */,

            Forms\Components\Select::make('navigation_group')
                ->label('Navigation Group')
                ->searchable()
                ->options(function () {
                    // Statische Liste der bestehenden Navigation Groups
                    return [
                        'Configuration' => 'Configuration',
                        'Test' => 'Test',
                    ];
                })
                ->getSearchResultsUsing(function (string $search) {
                    // Statische Liste laden
                    $tags = [
                        'configuration' => 'Configuration',
                        'test' => 'Test',
                    ];

                    // Den eingegebenen Suchbegriff prüfen
                    if ($search && !array_key_exists($search, $tags)) {
                        // Neuen Wert temporär hinzufügen
                        $tags[$search] = $search;
                    }

                    return $tags;
                })
                ->getOptionLabelUsing(function ($value) {
                    return $value ?: '';
                })
                ->afterStateUpdated(function ($state, callable $set) {
                    // Wenn ein neuer Wert eingegeben wurde, diesen direkt setzen
                    if ($state && !in_array($state, ['configuration', 'test'])) {
                        $set('navigation_group', $state);
                    }
                })
                ->reactive(),
            Forms\Components\TextInput::make('navigation_icon'),
            Forms\Components\TextInput::make('navigation_label'),

            ]
        )->columns(4);
    }

    public static function table(Table $table): Table
    {
        $fc = new FilamentFieldsController('resource_configs',0);
        $table_fields = $fc->getTableFields() ?? [];
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('resource'),
                Tables\Columns\TextColumn::make('navigation_group'),
                Tables\Columns\TextColumn::make('navigation_label'),
                Tables\Columns\TextColumn::make('navigation_icon'),
                ]
            )
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListResourceConfigs::route('/'),
            'create' => Pages\CreateResourceConfig::route('/create'),
            'edit' => Pages\EditResourceConfig::route('/{record}/edit'),
        ];
    }

    public static function getTableOptions(): array
    {
        $resources = Filament::getResources();
        $tables = [];

        foreach ($resources as $resourceClass) {
            if (!method_exists($resourceClass, 'getModel')) {
                continue;
            }

            $modelClass = $resourceClass::getModel();

            if (!class_exists($modelClass)) {
                continue;
            }

            $model = new $modelClass();
            $table = Str::singular($model->getTable());
            $label = $resourceClass::getPluralLabel() ?: Str::singular(class_basename($modelClass));
            $key = Str::studly($table)."Resource";
            $tables[$key] = $label;
        }

        asort($tables);
        return $tables;
    }
}

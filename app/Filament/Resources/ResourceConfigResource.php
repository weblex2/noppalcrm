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
use Illuminate\Support\Facades\DB;
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
        $resourceName = class_basename(static::class);
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
                ->reactive() ,

            Forms\Components\Select::make('navigation_group')
                ->label('Navigation Group')
                ->searchable()
                ->options(function () {
                    return ['' => '<none>'] + DB::table('resource_configs')
                        ->whereNotNull('navigation_group')
                        ->distinct()
                        ->orderBy('navigation_group')
                        ->pluck('navigation_group', 'navigation_group')
                        ->toArray();
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
            Forms\Components\Toggle::make('keep_filter'),
            Forms\Components\Toggle::make('show_in_nav_bar')->label('Show in Navbar'),
            ] 
        )->columns(4);
    }

    public static function table(Table $table): Table
    {
        $fc = new FilamentFieldsController('resource_configs',0);
        $table_fields = $fc->getTableFields() ?? [];
        return $table
            ->defaultSort('resource', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('resource')
                    ->icon(fn ($record) => str_contains($record->resource, '::')
                        ? 'heroicon-o-arrows-right-left'
                        : 'heroicon-o-cube')
                    ->searchable()

                    ->sortable()
                    ->formatStateUsing(function ($state) {
                        if (str_contains($state, '::')) {
                            return Str::of($state)
                                ->replace('::', ' → ')
                                ->afterLast('→ ')
                                ->ucfirst()
                                ->prepend(Str::of($state)->before('::') . ' → ');
                        }

                        return $state;
                    }),
                Tables\Columns\TextColumn::make('navigation_group'),
                Tables\Columns\TextColumn::make('navigation_label'),
                Tables\Columns\TextColumn::make('navigation_icon'),
                Tables\Columns\IconColumn::make('keep_filter')->label('Keep Filter')->boolean(),
                Tables\Columns\IconColumn::make('show_in_nav_bar')->label('Show in Navbar')->boolean(),
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

            // Resource selbst
            $model = new $modelClass();
            $table = Str::singular($model->getTable());
            $label = $resourceClass::getPluralLabel() ?: Str::headline(class_basename($modelClass));
            $key = Str::studly($table) . 'Resource';

            $tables[$key] = $label;

            // RelationManagers einbeziehen
            if (method_exists($resourceClass, 'getRelations')) {
                foreach ($resourceClass::getRelations() as $relationManagerClass) {
                    if (!class_exists($relationManagerClass)) {
                        continue;
                    }

                    try {
                        $reflection = new \ReflectionClass($relationManagerClass);
                        $property = $reflection->getProperty('relationship');
                        $property->setAccessible(true);
                        $relationName = $property->getValue();

                        if (!method_exists($modelClass, $relationName)) {
                            continue;
                        }

                        $relation = (new $modelClass)->{$relationName}();
                        if (!$relation instanceof \Illuminate\Database\Eloquent\Relations\Relation) {
                            continue;
                        }

                        $relatedModel = $relation->getRelated();
                        $relatedTable = Str::singular($relatedModel->getTable());
                        $relatedLabel = Str::headline(Str::plural(class_basename($relatedModel)));

                        // Key und Label zusammensetzen
                        $relationKey = Str::studly($table) . 'Resource::' . $relationName;
                        $relationLabel = $label . ' → ' . $relatedLabel;

                        $tables[$relationKey] = $relationLabel;
                    } catch (\Throwable $e) {
                        \Log::channel('crm')->warning("Fehler bei RelationManager $relationManagerClass: " . $e->getMessage());
                    }
                }
            }
        }

        $pageFiles = \File::files(app_path('Filament/Pages'));

        foreach ($pageFiles as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $className = 'App\\Filament\\Pages\\' . $file->getFilenameWithoutExtension();

            if (!class_exists($className)) {
                continue;
            }

            $base = class_basename($className);
            $label = Str::headline($base);
            $key = 'Page::' . $base;

            $tables[$key] = $label;
        }

        asort($tables);
        return $tables;
    }
}

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TableFieldsResource\Pages;
use App\Filament\Resources\TableFieldsResource\RelationManagers;
use App\Models\TableFields;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Filament\Forms\Components\ColorPicker;
use App\Http\Controllers\FilamentController;

class TableFieldsResource extends Resource
{
    protected static ?string $model = TableFields::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Configuration';

    protected static ?string $navigationLabel = 'Fields';

    protected static function getTitle(){
        return "Edit Fields";
    }

    //protected static ?string $title = 'Meine benutzerdefinierte Seite';

    public static function getPluralLabel(): string
    {
        return 'Configure Fields'; // Neuer Titel
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                ->schema([
                    Forms\Components\Section::make('Base Settings')
                    ->schema([

                        //Forms\Components\TextInput::make('user_id')->default(Auth::id()),
                        Forms\Components\Toggle::make('form')
                            ->helperText('Form or Table?')
                            ->default(false)  // ← Standardwert setzen
                            ->live()
                            ->disabled(fn (string $context) => $context === 'edit' && request()->get('duplicate') !== '1')
                            //->dehydrated(fn (string $context) => $context === 'create' && request()->get('duplicate') == '1')
                            ->dehydrated()
                            ->afterStateUpdated(fn ($state) => info('form-Toggle: ' . var_export($state, true))),
                        Forms\Components\Toggle::make('required'),
                        Forms\Components\Toggle::make('is_badge')->label('Is Badge'),
                        Forms\Components\Toggle::make('is_toggable')->label('Toggable'),
                        Forms\Components\Toggle::make('sortable'),
                        Forms\Components\Toggle::make('searchable'),
                        Forms\Components\Toggle::make('disabled')->columnSpan(2),
                        Forms\Components\Select::make('table')
                            ->label('Tabelle')
                            ->options(function () {
                                return array_filter(self::getTableOptions(), fn($label) => $label !== null && $label !== '');
                            })
                            ->required()
                            ->reactive() // wichtig für Reaktivität
                            ->disabled(fn (string $context) => $context === 'edit')
                            ->dehydrated(),
                         Forms\Components\Select::make('field')
                            ->label('Feld')
                            ->required()
                            ->reactive()
                            ->disabled(function (callable $get, string $context) {
                                return $context === 'edit' || ! $get('table');
                            })
                            ->options(fn (callable $get) => array_filter(self::getFieldOptions($get('table')), fn($label) => $label !== null && $label !== ''))
                            ->disabled(fn (callable $get) => ! $get('table'))
                            ->dehydrated(),

                        Forms\Components\TextInput::make('label')->required(),


                        Forms\Components\TextInput::make('order')->numeric(),


                        Forms\Components\TextInput::make('section')->numeric()->default(1),

                    ])->columns(4)->collapsible(),
                    Forms\Components\Section::make('Field Type')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->required()
                            ->reactive()
                            ->options([
                                'text' => 'Text',
                                'date' => 'Date',
                                'datetime' => 'DateTime',
                                'number' => 'Number',
                                'toggle' => 'Boolean',
                                'select' => 'Select',
                                'markdown' => 'Markdown',
                                'relation' => 'Relation'
                            ])
                            ->afterStateUpdated(function (callable $set, $state, callable $get) {
                                if ($get('type') !== 'relation') {
                                    $set('relation_table', null);
                                    $set('relation_show_field', null);
                                }
                            }),
                        Forms\Components\Select::make('select_options')
                            ->options(function () {
                                return DB::table('filament_configs')
                                    ->select('resource', 'field')
                                    ->where('type', 'option')
                                    ->distinct()
                                    ->get()
                                    ->mapWithKeys(function ($item) {
                                        $value = $item->resource . '.' . $item->field;     // gespeicherter Wert
                                        $label = ucfirst($item->resource) . ' | ' . ucfirst($item->field); // Anzeige
                                        return [$value => $label];
                                    })
                                    ->toArray();
                            })
                            ->searchable()
                            ->label('Dropdown Values'),
                        Forms\Components\Select::make('relation_table')
                            ->label('Relation Table')
                            ->reactive()
                            ->options(function (callable $get) {
                                $exclude = $get('table');

                                    return collect(File::files(app_path('Filament/Resources')))
                                    ->filter(fn ($file) => $file->getExtension() === 'php')
                                    ->map(fn ($file) => 'App\\Filament\\Resources\\' . $file->getFilenameWithoutExtension())
                                    ->filter(fn ($class) => class_exists($class) && str_ends_with(class_basename($class), 'Resource'))
                                    ->mapWithKeys(function ($class) {
                                        $basename = class_basename($class);           // z.B. "TestResource"
                                        $base = Str::replaceLast('Resource', '', $basename); // "Test"
                                        \Log::channel('crm')->info("base:".$base);

                                        $key = Str::of($base)->singular()->snake()->lower()->toString(); // "test"
                                        \Log::channel('crm')->info("key".$base);
                                        $label = Str::headline($base);                 // "Test"
                                        return [$key => $label];
                                    })
                                    ->reject(fn ($value, $key) => $key === $exclude)
                                    ->toArray();
                            })
                        ->disabled(fn (callable $get) => $get('type') !== 'relation')
                        ->searchable(),
                        Forms\Components\Select::make('relation_show_field')
                            ->label('Relation Field')
                            ->disabled(fn (callable $get) => $get('type') !== 'relation')
                            ->options(function (callable $get) {
                                $tableKey = $get('relation_table');

                                if (! $tableKey) {
                                    return [];
                                }

                                // Beispiel: aus "user" wird "users" (plural), wenn nötig
                                $tableName = \Illuminate\Support\Str::plural($tableKey);

                                // Spalten der Tabelle holen
                                try {
                                    $columns = DB::getSchemaBuilder()->getColumnListing($tableName);
                                } catch (\Exception $e) {
                                    $columns = [];
                                }

                                // Spalten in Dropdown-Format bringen
                                return collect($columns)
                                    ->mapWithKeys(fn ($col) => [$col => ucfirst(str_replace('_', ' ', $col))])
                                    ->toArray();
                            })
                            ->searchable(),

                    ])->columns(4)->collapsible(),

                    Forms\Components\Section::make('Advanced Settings')
                    ->schema([
                        Forms\Components\Select::make('color')->options([
                                'primary' => 'Primary',
                                'secondary' => 'Secondary',
                                'warning' => 'Warming',
                                'danger' => 'Danger',
                            ]),

                        Forms\Components\ColorPicker::make('bgcolor')->label('Background Color'),
                        Forms\Components\TextInput::make('icon')->label('Icon'),
                        Forms\Components\TextInput::make('icon_color')->label('Icon Color'),
                        Forms\Components\TextInput::make('link'),
                        Forms\Components\Select::make('link_target')->label('Link Target')->options(['_blank'=>'New Tab']),
                        Forms\Components\Textarea::make('format')->rows(5)->placeholder("z.B: return fn (string \state) => \App\Enums\CustomerStatusEnum::tryFrom(\$state)?->label() ?? \$state;")->columnSpanFull(),
                        Forms\Components\Textarea::make('extra_attributes')->rows(5)->placeholder("z.B: return fn (\$state) => ['style' => 'max-inline-size: 200px;];")->columnSpanFull(),
                    ])->columns(4)->collapsible()
            ])->columnSpan('full')
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id'),
                Tables\Columns\IconColumn::make('form')->boolean(),
                //Tables\Columns\TextColumn::make('user_id'),
                Tables\Columns\TextColumn::make('table'),
                Tables\Columns\TextColumn::make('field'),
                Tables\Columns\TextColumn::make('type'),
                Tables\Columns\TextColumn::make('label')->searchable(),
                Tables\Columns\TextColumn::make('icon')->icon(fn ($record) => $record->icon),
                Tables\Columns\TextColumn::make('icon_color'),
                Tables\Columns\IconColumn::make('link')->boolean()->getStateUsing(fn ($record) => !empty($record->link)),
                Tables\Columns\TextColumn::make('link_target'),
                Tables\Columns\IconColumn::make('sortable')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\IconColumn::make('searchable')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\IconColumn::make('disabled')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\IconColumn::make('required')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('bgcolor')
            ])
            ->persistFiltersInSession()
            ->recordAction(Tables\Actions\EditAction::class)
            ->recordUrl(null)
            ->filters([

                TernaryFilter::make('form')
                    ->label('Formular / Übersicht') // Label für den Filter
                    ->trueLabel('Formular') // Was angezeigt wird, wenn true (1)
                    ->falseLabel('Übersicht'), // Was angezeigt wird, wenn false (0)
                SelectFilter::make('table')
                    ->options(function () {
                         // Hole alle distinct "table" Werte aus der Tabelle
                        $options = TableFields::select('table')
                            ->distinct()
                            ->pluck('table', 'table')
                            ->toArray();
                        // Füge die Option "Alle" hinzu
                        // Wir fügen den speziellen Wert für "Alle" zu den Optionen hinzu
                        return  $options;
                    })

                    ->query(function ($query, $state) {
                        // Wenn kein Wert oder "Alle" ausgewählt ist (leerer String), keine Filterung anwenden
                        if (empty($state['value'])) {
                            return $query;
                        }

                        // Filtere nach dem ausgewählten "table"-Wert
                        return $query->where('table', $state['value']);
                    }),
            ])
            ->actions([
                 Tables\Actions\EditAction::make()
                    ->modalHeading('Feld bearbeiten')
                    ->modalWidth('6xl') // 🎯 HIER Modalbreite setzen
                    //->slideOver()
                    ->mutateFormDataUsing(function (array $data) {
                        if ($data['type'] === 'relation') {
                            $config['source'] = $data['table'];
                            $config['target'] = $data['relation_table'];
                            $config['method'] = 'BelongsTo';
                            $config['field'] = $data['field'];
                            $config['relation_name'] = $data['relation_table'];

                            app(FilamentController::class)->checkIfRelationExists($config);
                        }

                        return $data;
                    }),
                Tables\Actions\Action::make('duplicate')
                    ->label('Duplizieren')
                    ->icon('heroicon-o-document-duplicate')
                    ->action(function ($record, $data, $livewire) {
                        $params = $record->toArray();
                        unset($params['id']);
                        $params['label'] .= ' (Kopie)';

                        // Umleiten auf Create-Seite mit den Daten als Query-Parameter
                        return redirect(route('filament.admin.resources.table-fields.create', ['duplicate_data' => json_encode($params)]));
                    })
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function query(Builder $query): Builder
    {
        return $query->where('user_id', Auth::id());
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
            'index' => Pages\ListTableFields::route('/'),
            'create' => Pages\CreateTableFields::route('/create'),
            'edit' => Pages\EditTableFields::route('/{record}/edit'),
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
            $table = $model->getTable();
            $label = $resourceClass::getPluralLabel() ?: Str::plural(class_basename($modelClass));

            $tables[$table] = $label;

            // ➕ RelationManagers einbeziehen
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
                        $relatedTable = $relatedModel->getTable();
                        $relatedLabel = Str::plural(class_basename($relatedModel));
                        $key = $table . '::' . $relationName; // z. B. 'contacts::customer_contacts'
                        $tables[$key] = $label . ' → ' . $relatedLabel;
                    } catch (\Throwable $e) {
                        \Log::warning("RelationManager $relationManagerClass konnte nicht gelesen werden: {$e->getMessage()}");
                        continue;
                    }
                }

            }
        }

        asort($tables);
        return $tables;
    }


    public static function getFieldOptions(?string $tableKey): array
    {
        if (!$tableKey) {
            return [];
        }

        // Prüfen, ob es sich um einen RelationManager-Eintrag handelt
        if (str_contains($tableKey, '::')) {
            [$relationName, $table] = explode('::', $tableKey, 2);
        } else {
            $table = $tableKey;
        }

        if (!Schema::hasTable($table)) {
            return [];
        }

        return collect(Schema::getColumnListing($table))
            ->mapWithKeys(fn ($column) => [$column => $column ?? 'YYY'])
            ->toArray();
    }


    public static function mutateFormDataBeforeSave(array $data): array
    {
        $exists = TableFields::where('form', $data['form'])
            ->where('table', $data['table'])
            ->where('field', $data['field'])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'field' => 'Ein Eintrag mit diesen Werten existiert bereits.',
            ]);
        }

        return $data;
    }


}

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TableFieldsResource\Pages;
use App\Filament\Resources\TableFieldsResource\RelationManagers;
use App\Models\TableField;
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
use Filament\Tables\Enums\ActionsPosition;

class TableFieldsResource extends Resource
{
    protected static ?string $model = TableField::class;

    public static function getNavigationLabel(): string
    {
        $resourceName = class_basename(static::class);
        return \App\Models\ResourceConfig::where('resource', $resourceName)->value('navigation_label')
            ?? 'Fields'; // Fallback auf Standardlabel
    }

    public static function getNavigationIcon(): ?string{
        $resourceName = class_basename(static::class);
        return \App\Models\ResourceConfig::where('resource', $resourceName)->value('navigation_icon') ?? 'heroicon-o-rectangle-stack';
    }

    public static function getNavigationGroup(): ?string
    {
        $resourceName = class_basename(static::class);
        return \App\Models\ResourceConfig::where('resource', $resourceName)->value('navigation_group') ?? 'Configuration';
    }

    protected static ?string $recordTitleAttribute = "table";

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
                            ->options(function (callable $get) {
                                /* $tableOrResource = $get('table');
                                if (!$tableOrResource) {
                                    return [];
                                } */

                                return FilamentController::getResourcesDropdown();
                            })
                            ->required()
                            ->reactive() // wichtig für Reaktivität
                            //->disabled(fn (string $context) => $context === 'edit')
                            ->dehydrated(),
                         Forms\Components\Select::make('field')
                            ->label('Feld')
                            ->required()
                            ->reactive()
                            ->disabled(function (callable $get, string $context) {
                                return $context === 'edit' || ! $get('table');
                            })
                            ->options(fn (callable $get) => array_filter(FilamentController::getResourcesFieldDropdown($get('table')), fn($label) => $label !== null && $label !== ''))
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
                Tables\Columns\TextColumn::make('section')
                    ->label('Section')
                    ->formatStateUsing(function ($state, $record) {
                        return \App\Models\FilamentConfig::where('section_nr', $state)
                            ->where('resource', $record->table)
                            ->value('section_name') ?? '-';
                    }),
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
                Tables\Columns\ColorColumn::make('bgcolor')
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
                        $options = TableField::select('table')
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
                 Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()->color('primary'),
                    Tables\Actions\EditAction::make()
                        ->color('primary')
                        ->modalHeading('Feld bearbeiten')
                        ->modalWidth('6xl') // 🎯 HIER Modalbreite setzen
                        //->slideOver()
                        ->mutateFormDataUsing(function (array $data) {


                            if ($data['type'] === 'relation') {

                                // Prüfen, ob die Section ein Repeater ist
                                // und evtl eine neue Relation erstellen
                                $isRepeater = (array)\DB::table('filament_configs')
                                    ->where('resource', $data['table'])
                                    //->where('repeats_resource', $data['section'])
                                    ->where('section_nr', $data['section'])
                                    ->where('is_repeater', 1)
                                    ->first();

                                // Repeater
                                if (count($isRepeater)>0) {
                                    \Log::channel('crm')->info("Section {$data['section']} ist ein Repeater.");
                                    // Check if we have a relation between the repeater and the field
                                    $config['source'] = $isRepeater['repeats_resource'];
                                    $config['target'] = $data['relation_table'];
                                    $config['method'] = 'BelongsTo';
                                    $config['field'] = $data['field'];
                                    $config['relation_name'] = $data['relation_table'];
                                    $res = app(FilamentController::class)->checkIfRelationExists($config);
                                }

                                // Normal
                                else{
                                    $config['source'] = $data['table'];
                                    $config['target'] = $data['relation_table'];
                                    $config['method'] = 'BelongsTo';
                                    $config['field'] = $data['field'];
                                    $config['relation_name'] = $data['relation_table'];
                                    $res = app(FilamentController::class)->checkIfRelationExists($config);
                                }
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
            ], position: Tables\Enums\ActionsPosition::AfterColumns)
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ;
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


    public static function mutateFormDataBeforeSave(array $data): array
    {
        $exists = TableField::where('form', $data['form'])
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

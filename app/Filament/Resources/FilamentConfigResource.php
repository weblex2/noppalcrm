<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FilamentConfigResource\Pages;
use App\Filament\Resources\FilamentConfigResource\RelationManagers;
use App\Http\Controllers\FilamentController;
use App\Models\FilamentConfig;
use App\Models\TableField;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\View\Components\Forms\Components\HeroiconPicker;

class FilamentConfigResource extends Resource
{
    protected static ?string $model = FilamentConfig::class;

    public static function getNavigationLabel(): string
    {
        $resourceName = class_basename(static::class);
        return \App\Models\ResourceConfig::where('resource', $resourceName)->value('navigation_label')
            ?? parent::getNavigationLabel(); // Fallback auf Standardlabel
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



    public static function form(Form $form): Form
    {
        return $form
            ->schema([
             Forms\Components\Group::make()
                ->schema([
                Forms\Components\Section::make('Base Settings')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->required()
                            ->reactive()
                            ->options([
                                'navlink' => 'Navigation Link',
                                'option' => 'Option',
                                'filter' => 'Filter',
                                'section' => 'Section',
                            ])
                ]),
                Forms\Components\Section::make('Navigation Links')
                    ->schema([
                        Forms\Components\Select::make('resource')
                            ->required()
                            ->reactive()
                            ->options(function () {
                                return FilamentController::getResourcesDropdown(false,false);
                            }),
                        Forms\Components\Select::make('field')
                             ->options(fn (callable $get) => array_filter(FilamentController::getResourcesFieldDropdown($get('resource')), fn($label) => $label !== null && $label !== '')),
                        Forms\Components\TextInput::make('key')
                             ->required(fn ($get) => $get('type') === 'filter')
                             ->default(0)
                             ->maxLength(191),
                        Forms\Components\TextInput::make('value')
                            ->required()
                            ->maxLength(191),
                        Forms\Components\TextInput::make('order')
                            ->numeric(),
                        Forms\Components\TextInput::make('navigation_group'),
                        Forms\Components\TextInput::make('navigation_label'),
                        //Forms\Components\TextInput::make('section_name'),
                        Forms\Components\TextInput::make('icon')
                        /* HeroiconPicker::make('icon')
                            ->label('Icon auswählen')
                            ->icons([
                                'heroicon-o-user',
                                'heroicon-o-home',
                                'heroicon-o-document',
                                'heroicon-o-cog',
                                'heroicon-o-archive',
                                // usw. (du kannst hier alle verfügbaren Heroicons eintragen)
                            ])*/

                        ])
                        ->columns(4)
                        ->visible(fn ($get) => $get('type') === 'navlink')
                ])
                ->columnSpan('full'),
                Forms\Components\Section::make('Filter')
                 ->schema([
                    Forms\Components\Select::make('resource')
                            ->required()
                            ->options(function () {
                                return FilamentController::getResourcesDropdown(false,false);
                            }),
                    Forms\Components\TextInput::make('key')
                            ->required(),
                 ])
                 ->columns(4)
                 ->visible(fn ($get) => $get('type') === 'filter'),
                Forms\Components\Section::make('Options')
                 ->schema([
                    Forms\Components\Select::make('resource')
                            ->required()
                            ->options(function () {
                                return FilamentController::getResourcesDropdown(false,false);
                            }),
                    Forms\Components\TextInput::make('key')
                            ->required(),
                        Forms\Components\TextInput::make('value')
                            ->required()
                 ])
                 ->columns(4)
                 ->visible(fn ($get) => $get('type') === 'option'),

                Forms\Components\Section::make('Section')
                    ->schema([
                       Forms\Components\Toggle::make('is_repeater')->columnSpan(4),
                       Forms\Components\Select::make('resource')
                            ->required()
                            ->options(function () {
                                return FilamentController::getResourcesDropdown(false,false);
                            }),

                       Forms\Components\TextInput::make('section_nr'),
                       Forms\Components\TextInput::make('section_name'),
                       Forms\Components\Select::make('repeats_resource')
                            ->options(function () {
                                return FilamentController::getResourcesDropdown(false,false);
                            }),
                    ])
                 ->columns(4)
                 ->visible(fn ($get) => $get('type') === 'section')
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->icon(fn (string $state): string => match($state) {
                        'filter' => 'heroicon-o-funnel',
                        'navlink' => 'heroicon-o-rectangle-group',
                        'option' => 'heroicon-o-bars-4',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->iconColor('primary')
                    ->searchable(),
                Tables\Columns\TextColumn::make('resource')
                    ->searchable(),
                Tables\Columns\TextColumn::make('field')
                    ->searchable(),
                Tables\Columns\TextColumn::make('section_nr')->label('Section Nr'),
                Tables\Columns\TextColumn::make('section_name')->label('Section Name'),
                Tables\Columns\IconColumn::make('is_repeater')->boolean()->label('Is Repeater'),
                Tables\Columns\TextColumn::make('repeats_resource')->label('Repeats Ressource'),
                Tables\Columns\TextColumn::make('key')
                    ->searchable(),
                Tables\Columns\TextColumn::make('value')
                    ->searchable(),
                Tables\Columns\TextColumn::make('order')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('navigation_group'),
                Tables\Columns\TextColumn::make('navigation_label'),
                Tables\Columns\TextColumn::make('icon'),


                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->persistFiltersInSession()
            ->filters([
                SelectFilter::make('resource')
                    ->options(function () {
                         // Hole alle distinct "table" Werte aus der Tabelle
                        $options = TableField::select('table')
                            ->distinct()
                            ->pluck('table', 'table')
                            ->toArray();
                        return  $options;
                    }),
                SelectFilter::make('type')
                    ->options(function () {
                        $options = FilamentConfig::select('type')
                            ->distinct()
                            ->pluck('type', 'type')
                            ->toArray();
                        return  $options;
                    })
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
            'index' => Pages\ListFilamentConfigs::route('/'),
            'create' => Pages\CreateFilamentConfig::route('/create'),
            'edit' => Pages\EditFilamentConfig::route('/{record}/edit'),
        ];
    }
}

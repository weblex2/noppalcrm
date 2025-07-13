<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FilamentConfigResource\Pages;
use App\Filament\Resources\FilamentConfigResource\RelationManagers;
use App\Models\FilamentConfig;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

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
                Forms\Components\Select::make('type')
                    ->required()
                    ->options([
                        'navlink' => 'Navigation Link',
                        'option' => 'Option',
                        'filter' => 'Filter',
                    ]),
                Forms\Components\TextInput::make('resource')
                    ->required()
                    ->maxLength(191),
                Forms\Components\TextInput::make('field')
                    ->maxLength(191),
                Forms\Components\TextInput::make('key')
                    ->required()
                    ->maxLength(191),
                Forms\Components\TextInput::make('value')
                    ->required()
                    ->maxLength(191),
                Forms\Components\TextInput::make('order')
                    ->numeric(),
                Forms\Components\TextInput::make('navigation_group'),
                Forms\Components\TextInput::make('navigation_label'),
                Forms\Components\TextInput::make('icon'),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('resource')
                    ->searchable(),
                Tables\Columns\TextColumn::make('field')
                    ->searchable(),
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
            ->filters([
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

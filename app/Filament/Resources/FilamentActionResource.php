<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FilamentActionResource\Pages;
use App\Filament\Resources\FilamentActionResource\RelationManagers;
use App\Models\FilamentAction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FilamentActionResource extends Resource
{
    protected static ?string $model = FilamentAction::class;

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
                Forms\Components\TextInput::make('resource')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('action_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('label')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('icon')
                    ->maxLength(255),
                Forms\Components\Select::make('type')
                    ->required()
                    ->options(['header' => 'Header', 'row' => "Row"])
                    ->default('header'),
                Forms\Components\TextInput::make('color')
                    ->maxLength(255),
                Forms\Components\TextInput::make('view')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Toggle::make('modal_submit_action')
                    ->label('Submit erlaubt')
                    ->default(false)
                    ->required(),
                Forms\Components\Toggle::make('modal_cancel_action')
                    ->label('Abbrechen erlaubt')
                    ->default(false)
                    ->required()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('resource')
                    ->searchable(),
                Tables\Columns\TextColumn::make('action_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('label')
                    ->searchable(),
                Tables\Columns\TextColumn::make('icon')
                    ->searchable(),
                Tables\Columns\TextColumn::make('color')
                    ->searchable(),
                Tables\Columns\TextColumn::make('view')
                    ->searchable(),
                Tables\Columns\TextColumn::make('modal_submit_action')
                    ->searchable(),
                Tables\Columns\TextColumn::make('modal_cancel_action')
                    ->searchable(),
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
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListFilamentActions::route('/'),
            'create' => Pages\CreateFilamentAction::route('/create'),
            'edit' => Pages\EditFilamentAction::route('/{record}/edit'),
        ];
    }
}

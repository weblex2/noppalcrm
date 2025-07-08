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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Http\Controllers\FilamentFieldsController;

class ResourceConfigResource extends Resource
{
    protected static ?string $model = ResourceConfig::class;


    public static function getNavigationLabel(): string
    {
        $resourceName = class_basename(static::class); // z. B. ResourceConfigResource

        return \App\Models\ResourceConfig::where('resource', $resourceName)->value('label')
            ?? parent::getNavigationLabel(); // Fallback auf Standardlabel
    }

    public static function getNavigationIcon(): ?string{
        return \App\Models\ResourceConfig::where('resource', 'ResourceConfigResource')->value('navigation_icon') ?? 'heroicon-o-rectangle-stack';
    }

    public static function getNavigationGroup(): ?string
    {
        return \App\Models\ResourceConfig::where('resource', 'ResourceConfigResource')->value('navigation_group') ?? null;
    }

    public static function form(Form $form): Form
    {
        $fc = new FilamentFieldsController('resource_configs',1);
        $schema = $fc->getSchema() ?? [];
        return $form->schema($schema);
    }

    public static function table(Table $table): Table
    {
        $fc = new FilamentFieldsController('resource_configs',0);
        $table_fields = $fc->getTableFields() ?? [];
        return $table
            ->columns($table_fields)
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
            'index' => Pages\ListResourceConfigs::route('/'),
            'create' => Pages\CreateResourceConfig::route('/create'),
            'edit' => Pages\EditResourceConfig::route('/{record}/edit'),
        ];
    }
}

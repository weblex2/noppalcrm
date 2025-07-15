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
use Filament\Tables\Actions;
use App\Http\Controllers\FilamentFieldsController;
use Illuminate\Support\Facades\File;
use App\Models\FilamentConfig;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\SelectFilter;
use App\Http\Controllers\FilamentHelper;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\FilamentController;

class FilamentActionResource extends Resource
{
    protected static ?string $model = FilamentAction::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationLabel(): string
    {
        $resourceName = class_basename(static::class); // z. B. ResourceConfigResource
        return \App\Models\ResourceConfig::where('resource', $resourceName)->value('navigation_label')
            ?? parent::getNavigationLabel(); // Fallback auf Standardlabel
    }

    public static function getNavigationGroup(): ?string
    {
        $resourceName = class_basename(static::class); // ergibt z. B. "HouseResource"
        return \App\Models\ResourceConfig::where('resource', $resourceName)->value('navigation_group') ?? null;
    }

    public static function getNavigationIcon(): ?string
    {
        $resourceName = class_basename(static::class); // ergibt z. B. "HouseResource"
        return \App\Models\ResourceConfig::where('resource', $resourceName)->value('navigation_icon') ?? 'heroicon-o-rectangle-stack';
    }

    public static function shouldRegisterNavigation(): bool
    {
        $resourceName = class_basename(static::class);
        return \App\Models\ResourceConfig::where('resource', $resourceName)->value('show_in_nav_bar') ?? true;
    }

    public static function getNavigationSort(): ?int
    {
        $resourceName = class_basename(static::class);
        return \App\Models\ResourceConfig::where('resource', $resourceName)->value('navigation_sort') ?? null;
    }

    public static function form(Form $form): Form
    {
        $fc = new FilamentFieldsController('filament_actions',1);
        $schema = $fc->getSchema() ?? [];
        return $form
            ->schema($schema);
    }


    public static function table(Table $table): Table
    {
        $fc = new FilamentFieldsController('filament_actions',0);
        $tableFields = $fc->getTableFields() ?? [];

        // Dynamische Filter generieren
        $tableFilters = FilamentController::getTableFilter('filament_actions');

        return $table
            ->columns($tableFields)
            ->filters($tableFilters)
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Actions\Action::make('Exportieren')
                    ->label('Excel Export')
                    ->icon('heroicon-o-folder-arrow-down')
                    ->action(function (array $data, $livewire) {
                        $records = $livewire->getFilteredTableQuery()->get(); // Das funktioniert bei Table-Components
                        $records = FilamentHelper::excelExport($records);
                        // Anonyme Export-Klasse
                        return Excel::download($records, 'export.xlsx');
                    }),
                Actions\Action::make('addField')
                    ->label('Feld hinzufügen')
                    ->icon('heroicon-o-plus-circle')
                    ->modalContent(function ($record) {
                        return view('filament.actions.add-db-field-modal', [
                            'tableName' => 'filament_actions',
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false),

            ]);
    }
    public static function getRelations(): array
    {
        $relations = [];
        $path = app_path('Filament/Resources/FilamentActionResource/RelationManagers');
        if (file_exists($path)){
        $relations =  collect(File::files($path))
            ->map(fn ($file) => 'App\\Filament\\Resources\\FilamentActionResource\\RelationManagers\\' . $file->getFilenameWithoutExtension())
            ->filter(fn ($class) => class_exists($class))
            ->values()
            ->toArray();
        }
        return $relations;
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

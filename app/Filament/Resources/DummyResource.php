<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DummyResource\Pages;
use App\Filament\Resources\DummyResource\RelationManagers;
use App\Http\Controllers\FilamentFieldsController;
use App\Models\Dummy;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DummyResource extends Resource
{
    protected static ?string $model = Dummy::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public ?string $resourceName = null;

    public function mount(): void
    {
        parent::mount();
    }

    public function getBreadcrumbs(): array
    {
        return []; // leeres Array → keine Breadcrumbs
    }

    private static function addSuffixIconToTextInputs(array $fields): array
{
    return array_map(function ($field) {
        // nur TextInput bekommt das Icon
        if ($field instanceof \Filament\Forms\Components\TextInput) {
            $field->suffixIcon('heroicon-o-information-circle');
        }

        // falls Container → rekursiv
        if (method_exists($field, 'getChildren')) {
            $children = $field->getChildren();
            $field->schema(self::addSuffixIconToTextInputs($children));
        }

        return $field;
    }, $fields);
}

   public static function form(Form $form): Form
    {
        $fc = new FilamentFieldsController('companies', 1, true);
        $schema = $fc->getSchema() ?? [];

        $schema = self::addSuffixIconToTextInputs($schema);

        // Button + Modal oben hinzufügen
        array_unshift($schema,
            \Filament\Forms\Components\Actions::make([
                \Filament\Forms\Components\Actions\Action::make('addField')
                    ->label('Neues Feld hinzufügen')
                    ->icon('heroicon-o-plus')
                    ->color('primary')

                    // Modal
                    ->modalHeading('Neues Feld erstellen')
                    ->modalWidth('2xl')

                    // Modal-Inhalt: dein Livewire-Component
                    ->modalContent(function ($record) {
                        return view('filament.actions.add-db-field-modal', [ 'tableName' => 'companies', ]); })
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false),
            ])
            ->columnSpan('full')
        );

        return $form->schema($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->query(fn () => \App\Models\Dummy::query()->whereRaw('0 = 1'))
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
            'index' => Pages\ListDummies::route('/'),
            'create' => Pages\CreateDummy::route('/create'),
            'edit' => Pages\EditDummy::route('/{record}/edit'),
        ];
    }
}

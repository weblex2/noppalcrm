<?php
namespace App\Livewire;

use Livewire\Attributes\Modelable;
use Livewire\Component;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use App\Http\Controllers\FilamentAddFieldController;
use Filament\Notifications\Notification;
use App\Models\TableField;


class AddFieldComponent extends Component implements HasForms
{
    use InteractsWithForms;

    #[Modelable]
    public $fields = [];
    public $newFieldName = '';
    public $newFieldType = '';
    public $newFieldLength = '';
    public $newFieldNullable = true;
    public $newFieldUnique = false;
    public $newFieldDefaultValue = '';
    public $result = false;
    public string $tableName = '';

    public function mount($tableName = '')
    {
        $this->tableName = $tableName;
    }

    protected function getForms(): array
    {
        return [
            'form' => $this->makeForm()
                ->schema([
                    TextInput::make('tableName')
                        ->default($this->tableName)
                        ->disabled()
                        ->dehydrated(false), // <-- wichtig!
                    TextInput::make('newFieldName') // Setze den Namen auf das Property
                        ->label('Feldname')
                        ->placeholder('z. B. benutzer_status')
                        ->helperText('Nur Buchstaben, Zahlen und Unterstriche')
                        ->rules(['required', 'regex:/^[a-zA-Z_][a-zA-Z0-9_]*$/'])
                        ->reactive()
                        ->required(),

                    Select::make('newFieldType') // Setze den Namen auf das Property
                        ->label('Feldtyp')
                        ->options([
                            'string' => 'VARCHAR (string)',
                            'text' => 'TEXT',
                            'integer' => 'INTEGER',
                            'boolean' => 'BOOLEAN',
                            'date' => 'DATE',
                            'datetime' => 'DATETIME',
                            'float' => 'FLOAT',
                            'decimal' => 'DECIMAL',
                            'json' => 'JSON',
                        ])
                        ->searchable()
                        ->required()
                        ->reactive(),

                    TextInput::make('newFieldLength') // Setze den Namen auf das Property
                        ->label('Länge'),

                    Toggle::make('newFieldNullable') // Setze den Namen auf das Property
                        ->label('Darf NULL sein')
                        ->default(true)
                        ->inline(),

                    Toggle::make('newFieldUnique') // Setze den Namen auf das Property
                        ->label('Muss eindeutig sein (UNIQUE)')
                        ->default(false)
                        ->inline(),

                    TextInput::make('newFieldDefaultValue') // Setze den Namen auf das Property
                        ->label('Standardwert (optional)')
                        ->placeholder('z. B. aktiv oder 0')
                        ->helperText('Wird verwendet, falls kein Wert gesetzt ist')
                        ->reactive(),
                ]),
        ];
    }

    public function addField()
    {
        // Validierung auf die Formularwerte
        $this->form->validate();

        // Erstelle das Feld-Array
        $fieldData = [
            'tablename' => $this->tableName,
            'name' => $this->newFieldName,
            'type' => $this->newFieldType,
            'length' => $this->newFieldLength,
            'isNullable' => $this->newFieldNullable,
            'isUnique' => $this->newFieldUnique,
            'defaultValue' => $this->newFieldDefaultValue,
        ];

        // Aufruf der statischen Methode FilamentHelper::createField
        $this->result = FilamentAddFieldController::createField($fieldData);
        if ($this->result['status']=='Success'){  // in this case 0 means success
            Notification::make()
                ->title('Erfolg!')
                ->body('Das Feld wurde erfolgreich hinzugefügt.')
                ->success() // Du kannst auch `error()`, `warning()` oder `info()` verwenden
                ->send();
        }
        else {  // in this case 0 means success
            Notification::make()
                ->title('Erfolg!')
                ->body('Error:')
                ->danger() // Du kannst auch `error()`, `warning()` oder `info()` verwenden
                ->send();
        }

        // Formular zurücksetzen
        $this->form->fill();
    }

    public function render()
    {
        return view('livewire.add-field-component');
    }
}

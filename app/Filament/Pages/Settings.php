<?php

namespace App\Filament\Pages;

use App\Models\GeneralSetting;
use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Actions\Action;
use Illuminate\Support\Facades\Cache;
use Filament\Notifications\Notification;
use Filament\Forms\Concerns\InteractsWithForms;

class Settings extends Page
{
    use InteractsWithForms;


    protected static ?string $navigationIcon = 'heroicon-o-cog';
    protected static string $view = 'filament.pages.settings';
    protected static ?string $navigationLabel = 'Settings';

    protected static ?string $navigationGroup = 'Configuration';

    public $settings = [];


    public function form(Form $form): Form
    {
        return $form
            ->schema($this->getFormSchema())
            ->statePath('settings'); // Das ist wichtig!
    }

    public function mount(): void
    {
        $settings = GeneralSetting::all()->pluck('value', 'field')->toArray();

        foreach ($settings as $key => $value) {
            $this->settings[$key] = $value;
        }

        $this->form->fill($this->settings);
    }

    protected function getFormSchema(): array
    {
        return [
            // Allgemeine Einstellungen
            Forms\Components\Section::make('General')
                ->schema([
                    Forms\Components\TextInput::make('site_name')
                        ->label('Site Name')
                        ->required(),
                    Forms\Components\Toggle::make('email_notifications')
                        ->label('Enable Email Notifications'),
                ])
                ->collapsible(),  // Kollapsbar

            // E-Mail Einstellungen
            Forms\Components\Section::make('IMAP')
                ->schema([

                    Forms\Components\TextInput::make('imap_server')
                        ->label('Server'),
                    Forms\Components\TextInput::make('imap_port')
                        ->label('Port')
                        ->numeric()
                        ->default(995),  // Beispiel Port
                    Forms\Components\TextInput::make('imap_encryption')
                        ->label('Encryption')
                        ->default('ssl'),
                ])
                ->columns(3)
                ->collapsible(),  // Kollapsbar

            // E-Mail Einstellungen
            Forms\Components\Section::make('SMTP')
                ->schema([

                    Forms\Components\TextInput::make('smtp_server')
                        ->label('Server'),
                    Forms\Components\TextInput::make('smtp_port')
                        ->label('Port')
                        ->numeric()
                        ->default(995),  // Beispiel Port
                    Forms\Components\TextInput::make('smtp_encryption')
                        ->label('Encryption')
                        ->default('ssl'),
                ])
                ->columns(3)
                ->collapsible(),  // Kollapsbar

            // Sonstige Einstellungen
            Forms\Components\Section::make('Other')
                ->schema([
                    // Hier kannst du andere Felder hinzufügen
                ])
                ->collapsible(),  // Kollapsbar
        ];
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        // Speichere die Einstellungen in der Datenbank (Tabelle general_settings)
        foreach ($data as $field => $value) {
            // Überprüfen, ob bereits ein Wert für dieses Feld existiert
            $setting = \App\Models\GeneralSetting::updateOrCreate(
                ['field' => $field],  // Bedingung: Suche nach 'field'
                ['value' => $value]    // Wenn nicht vorhanden, setze den Wert
            );
        }

        // Cache aktualisieren
        Cache::put('settings', $data);

        // Benachrichtigung senden
        Notification::make()
            ->title('Settings saved successfully!')
            ->success()
            ->send();
        // Wenn du die Benachrichtigung in der Datenbank speichern willst:
        //auth()->user()->notify(new GeneralNotification('Settings saved successfully!'));
    }

    public function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Settings')
                ->action('submit')
                ->color('primary')
                ->icon('heroicon-o-check')
                ->requiresConfirmation(true), // optional
            Action::make('addField')
                        ->label('Feld hinzufügen')
                        ->icon('heroicon-o-plus-circle')
                        ->modalContent(function ($record) {
                            return view('filament.actions.add-db-field-modal', [
                                'tableName' => 'settings',
                            ]);
                        })
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false),
        ];
    }
}

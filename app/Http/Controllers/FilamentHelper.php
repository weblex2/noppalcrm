<?php

namespace App\Http\Controllers;

use Filament\Tables\Columns\Column;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Quote;
use Illuminate\Support\Facades\Log;
use App\Exports\CustomExport;
use App\Models\GeneralSetting;
use App\Models\FilTableFields;
use Illuminate\Support\Facades\Artisan;



class FilamentHelper
{
    private $data;

    public static function readSetting($field){
        $setting = GeneralSetting::where('field', $field)->first();
        // Wenn der Datensatz existiert, gibt den Wert zurück, sonst null
        return $setting ? $setting->value : false;
    }

    public static function renderPdf($id){
        $quote = Quote::with(['customer', 'quoteProducts.product'])->find($id);
        $quote->quoteProducts = $quote->quoteProducts->sortBy('reoccurance');
        $pdf['content'] = Pdf::loadView('filament.pdf.quote', ['quote' => $quote]);
        $pdf['quote_number'] = $quote->quote_number;
        return $pdf;
    }

    public static function generateFile($id)
    {
        $quote = Quote::with(['customer', 'quoteProducts.product'])->find($id);

        if (!$quote) {
            throw new \Exception("Quote mit ID $id nicht gefunden.");
        }

        $quote->quoteProducts = $quote->quoteProducts->sortBy('reoccurance');

        $pdf = Pdf::loadView('filament.pdf.quote', ['quote' => $quote]);

        $filename = 'Angebot_' . $quote->quote_number . '.pdf';
        $path = storage_path('app/public/quotes/' . $filename);

        // Verzeichnis sicherstellen
        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0775, true);
        }

        // PDF speichern
        $pdf->save($path);
        return $path;
    }

    public static function excelExport($data){
        $exportData = [];
        $header = array_keys($data[0]->getAttributes());
        $exportData[] = $header;
        foreach ($data as $item) {
            $row = [];
            foreach ($item->getAttributes() as $key => $value) {
                $row[ucwords(str_replace('_', ' ', $key))] = $value;
            }
            $exportData[] = $row;
        }
        return new CustomExport($exportData);
    }

    public static function createField(array $field){
        $field['name'] = self::sanitizeMysqlFieldName($field['name']);
        $filename = self::createMigrationFile($field);
        $result = self::execMigration($filename);
        // if migration was not successful delete it
        if ($result['status']=='Fail'){
            unlink($filename);
        }
        else{

            switch($field['type']){
                case 'string':
                    $type = "text";
                    break;
                case 'date':
                    $type = "text";
                    break;
                default:
                    $type = "text";
                    break;
            }

            // Create Field in Table Fields
            $newfield = [
                'form' => 0,
                'user_id' => 0,
                'label' => $label = ucwords(str_replace('_', ' ', $field['name'])),
                'table' => $field['tablename'],
                'field' => $field['name'],
                'type' => $type,
            ];
            FilTableFields::create($newfield);
            $newfield['form']=1;
            FilTableFields::create($newfield);
            // Delete the migration file
            unlink($filename);

        }
        return $result;
    }
    private static function createMigrationFile(array $field)
    {
        $table = $field['tablename']; // z. B. von der Resource ableiten

        $migrationName = 'add_' . $field['name'] . '_to_' . $table . '_table';

        $migrationCommand = 'make:migration ' . $migrationName . ' --table=' . $table;
        Artisan::call($migrationCommand);

        // Migration anpassen
        $path = collect(glob(database_path('migrations/*.php')))
            ->filter(fn ($f) => str_contains($f, $migrationName))
            ->last();

        $stub = self::generateFieldLine($field);
        file_put_contents($path, str_replace('//', $stub, file_get_contents($path)));
        return $path;
    }

    protected static function generateFieldLine(array $field): string
    {
        $line = "\$table->{$field['type']}('{$field['name']}'";

        if (!empty($field['length']) && $field['type'] === 'string') {
            $line .= ", {$field['length']}";
        }

        $line .= ");";

        return "            {$line}\n";
    }

    private static function execMigration($path){
        $command  = "migrate";
        $status = "";
        try {
            Artisan::call($command);
            $status = "Success";
            $output = Artisan::output();
        } catch (\Exception $e) {
            $status = "Fail";
            $output = $e->getMessage();
        }

        $result = [
            'status' => $status,
            'output' => $output,
        ];

        return $result;
    }

    private static function sanitizeMysqlFieldName(string $input): string
    {
        // Kleinbuchstaben
        $field = strtolower($input);

        // Umlaute ersetzen
        $field = str_replace(
            ['ä', 'ö', 'ü', 'ß'],
            ['ae', 'oe', 'ue', 'ss'],
            $field
        );

        // Leerzeichen und Sonderzeichen durch Unterstriche ersetzen
        $field = preg_replace('/[^a-z0-9_]+/', '_', $field);

        // Mehrere Unterstriche zusammenfassen
        $field = preg_replace('/_+/', '_', $field);

        // Vorne und hinten Unterstriche entfernen
        $field = trim($field, '_');

        // Fängt der Feldname mit einer Zahl an? Dann Prefix hinzufügen
        if (preg_match('/^[0-9]/', $field)) {
            $field = 'f_' . $field;
        }

        // Optional: auf maximale Länge von 64 Zeichen beschränken
        return substr($field, 0, 64);
    }


    public function createResource($name){
        return "Yes!";
    }
}

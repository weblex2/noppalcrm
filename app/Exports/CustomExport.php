<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromArray;

class CustomExport implements FromArray
{
    protected $data;

    // Konstruktor, um die Daten zu übergeben
    public function __construct($data)
    {
        $this->data = $data;
    }

    // Die array()-Methode wird benötigt, um die Daten für den Export zurückzugeben
    public function array(): array
    {
        return $this->data;
    }
}

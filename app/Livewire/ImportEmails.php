<?php

namespace App\Livewire;

use Livewire\Component;
use App\Http\Controllers\MailController;

class ImportEmails extends Component
{
    public array $emails = [];

    public function import()
    {
        // Controller manuell aufrufen
        $controller = new MailController();
        $this->emails = $controller->importEmails(); // Gibt Array zurück
    }

    public function render()
    {
        return view('livewire.import-emails');
    }
}

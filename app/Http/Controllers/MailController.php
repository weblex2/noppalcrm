<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Facades\Client;
use Carbon\Carbon; // für das Datum
use App\Models\GeneralSetting;
use App\Models\Contact;
use App\Models\ContactPerson;
use Illuminate\Support\Facades\Storage;
use App\Models\Document;


class MailController extends Controller
{
    public function importEmails($customer_id = null)
{
    $result = [
            'status' => 'success',
            'saved' => 0,
            'errors' => [],
            'attachements' => [],
            'documents_imported' => 0,
            'emails_imported' => 0,
        ];
    $user = Auth::user();
    $email = $user->email;
    $email = "alex@noppenberger.org";
    $imapPassword = decrypt($user->user01);
    $host = GeneralSetting::where('field', 'imap_server')->value('value');
    $port = GeneralSetting::where('field', 'imap_port')->value('value') ?? 993;
    $port = 993;
    $encryption = GeneralSetting::where('field', 'imap_encryption')->value('value') ?? 'ssl';

    $checkEmails = ContactPerson::select('id', 'email')
                ->get()
                ->pluck('id', 'email')
                ->toArray();

    $cm = new ClientManager();

    try {
        $client = $cm->make([
            'host' => $host,
            'port' => $port,
            'encryption' => $encryption,
            'validate_cert' => true,
            'username' => $email,
            'password' => $imapPassword,
            'protocol' => 'imap',
        ]);
        $client->connect();
    } catch(\Exception $e){
        \Log::channel('crm')->info('Could not create Mail Client!');
        return ['error'=> $e->getMessage()];
    }




    // Datum
    $to = Carbon::now('UTC')->format('d-M-Y');
    $from = Carbon::now('UTC')->subDays(1)->format('d-M-Y');
    $from = "13-Jul-2025";

    // 📥 INBOX & 📤 SENT Folder
    $folders = ['INBOX', 'Sent'];

    foreach ($folders as $folderName) {
        try {
            $folder = null;
            $folder = $client->getFolder($folderName);
            if ($folderName=="Sent"){
                $this->folderName = "Sent";
            }
        } catch (\Exception $e) {
            $result['errors'][] = "Ordner '$folderName' konnte nicht geladen werden: " . $e->getMessage();
            continue;
        }

        $messages = $folder->query()
            ->since($from)
            ->get();
        $existingEmails = array_keys($checkEmails);
        foreach ($messages as $message) {
            $parsedEmail = $this->parseSingleEmail($message);
            if (!in_array($parsedEmail['from'],$existingEmails)){
                continue;
            }

            $res = $this->saveEmail($parsedEmail, $checkEmails);

            if ($res) {
                if ($result['status'] != "error") {
                    $result['status'] = "success";
                }

                $result['emails_imported']++;

                if (!empty($res['attachements'])) {
                    $result['attachements'][] = $res['attachements'];
                    $result['documents_imported'] += $res['attachements'][0]['saved'];
                }
            }
        }

        return ['count' => count($messages)];

    }


    return $result;

    }


    private function parseSingleEmail($message): array
    {
        try {
            // Eindeutige ID (Message-ID oder UUID)
            $messageId = $message->getMessageId() ?? (string) Str::uuid();

            // Metadaten extrahieren
            $subject = $message->getSubject() ?? 'Kein Betreff';
            $from = $message->getFrom()[0]->mail ?? 'Unbekannter Absender';
            //dd($message->getTo());
            $to = collect($message->getTo()->toArray() ?? [])
            ->map(fn($address) => $address->mail ?? null)
            ->filter()
            ->implode(', ') ?: 'Unbekannter Empfänger';

            // Datum extrahieren und formatieren
            $rawDate = $message->getDate(); // String oder Attribute-Objekt
            $date = $rawDate ? Carbon::parse($rawDate)->format('Y-m-d H:i:s') : 'Unbekanntes Datum';

            // Textkörper extrahieren
            $plainText = $message->hasTextBody() ? $message->getTextBody() : null;
            $htmlText = $message->hasHTMLBody() ? $message->getHTMLBody() : null;

            // Anhänge verarbeiten
            $attachments = [];
            $attachmentStoragePath = 'attachments';

            if ($message->hasAttachments()) {
                /* if (!Storage::exists($attachmentStoragePath)) {
                    Storage::makeDirectory($attachmentStoragePath);
                } */

                foreach ($message->getAttachments() as $attachment) {
                    $filename = $attachment->getName();
                    $uniqueFilename = time() . '_' . $filename;
                    $filePath = "$attachmentStoragePath/$uniqueFilename";

                    Storage::put($filePath, $attachment->getContent());

                    $attachments[] = [
                        'filename' => $filename,
                        'stored_path' => $filePath,
                        'size' => $attachment->getSize(),
                        'mime_type' => $attachment->getMimeType(),
                        'content' => $attachment->getContent(),
                    ];
                }
            }

            return [
                'id' => $messageId,
                'subject' => $subject,
                'from' => $from,
                'to' => $to,
                'date' => $date,
                'plain_text' => $plainText,
                'html_text' => $htmlText,
                'attachments' => $attachments,
            ];

        } catch (\Exception $e) {
            return [
                'id' => null,
                'error' => 'Fehler beim Parsen der E-Mail: ' . $e->getMessage(),
            ];
        }
    }

    private function saveEmail($parsedEmail, $checkEmails){
        $result = [
            'status' => 'success',
            'saved' => 0,
            'errors' => [],
            'attachements' => [],
        ];

        if (!$this->emailExists($parsedEmail['id'])){
            $contact = new Contact();
            $from = $parsedEmail['from'];
            $to = $parsedEmail['to'];
            $customer_id = array_values($checkEmails)[0] ?? false;


            /* if (!$customer_id && $this->folderName="Sent"){
                $customer_id = $this->customerEmailIds[$to] ?? false;
            } */

            if (!$customer_id){
                return false;
            }

            // Benutzer-ID setzen (Fallback: aktueller Benutzer oder 1)
            $userId = $this->user_id ?? auth()->id() ?? 1;
            $contact->external_id   = $parsedEmail['id']->first();
            $contact->customer_id   = $customer_id != false ? $customer_id : null;
            $contact->type          = 'email';
            $contact->from          = $parsedEmail['from'];
            $contact->to            = $parsedEmail['to'];
            $contact->contacted_at  = $parsedEmail['date'];
            $contact->subject       = $parsedEmail['subject']->first();
            $contact->details       = $parsedEmail['plain_text'];
            $success = $contact->save();
            if ($success){
                $contact_id = $contact->id;
                $result['status'] = "success";
                $result['saved'] = 1;
                $attResult =  $this->saveAttachement($parsedEmail, $contact_id, $customer_id , $userId);
                if (($attResult['status']=='success' && $attResult['saved']!= 0) || $attResult['status']!="success"){
                    $result['attachements'][]  = $attResult;
                }
            }
            else{
                $result['status'] = "error";
                $result['error'] = 1;
            }
            return $result;
        }
    }

    private function emailExists($external_id){
        $count =  Contact::where('external_id',$external_id)->count();
        return $count;
    }

    private function saveAttachement(array $parsedEmail, $contact_id, ?int $customerId = null, int $userId ): array
    {



        $result = [
            'status' => 'success',
            'saved' => 0,
            'errors' => [],
        ];

        try {
            // Überprüfe, ob die E-Mail gültig ist
            if (isset($parsedEmail['error']) || !isset($parsedEmail['attachments'])) {
                $result['status'] = 'error';
                $result['errors'][] = 'Ungültige E-Mail-Daten: ' . ($parsedEmail['error'] ?? 'Keine Anhänge vorhanden');
                return $result;
            }


            // Anhänge speichern
            foreach ($parsedEmail['attachments'] as $attachment) {
                try {
                    $res = Document::create([
                        'external_id' => $parsedEmail['id'], // Verknüpfung mit contacts.external_id
                        'contact_id' => $contact_id,
                        'customer_id' => $customerId,
                        'filename' => $attachment['filename'],
                        'size' => $attachment['size'],
                        'mime_type' => $attachment['mime_type'],
                        'content' => $attachment['content'],
                        'user' => $userId,
                    ]);
                    $result['saved']++;
                } catch (\Exception $e) {
                    $result['status'] = 'error';
                    $result['errors'][] = 'Fehler beim Speichern des Dokuments (' . $attachment['filename'] . '): ' . $e->getMessage();
                    \Log::error('Fehler beim Speichern des Dokuments', [
                        'filename' => $attachment['filename'],
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            return $result;

        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['errors'][] = 'Allgemeiner Fehler beim Speichern der Dokumente: ' . $e->getMessage();
            Log::error('Allgemeiner Fehler beim Speichern der Dokumente', ['error' => $e->getMessage()]);
            return $result;
        }
    }
}

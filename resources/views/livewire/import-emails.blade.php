<div x-data>
    <x-filament::button wire:click="import" color="primary">
        📥 Import Emails
    </x-filament::button>

    @if (!empty($emails))
        <div class="mt-4 space-y-2">
            <h3 class="font-bold">Importierte E-Mails:</h3>
            <ul class="list-disc list-inside">
                @foreach ($emails as $email)
                    <li>{{ is_array($email) ? json_encode($email) : $email }}</li>
                @endforeach
            </ul>

            <x-filament::button
                color="secondary"
                x-on:click="window.dispatchEvent(new CustomEvent('close-modal'))"
            >
                ❌ Schließen
            </x-filament::button>
        </div>
    @endif
</div>

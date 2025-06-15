<div>
    <!-- Filament-Formular rendern -->
    {{ $this->form }}

    <!-- Button zum Hinzufügen -->
    <x-filament::button
        wire:click="addField"
        class="mt-4 mb-4 filament-button filament-button-primary"
        type="button"
    >
        Hinzufügen
    </x-filament::button>

    @if ($result)
        <div>{{ $result['output'] }}</div>
    @endif

    <!-- Liste der hinzugefügten Felder -->
    {{-- @if (!empty($fields))
        <ul class="mt-4 space-y-2">
            @foreach ($fields as $index => $field)
                <li class="flex items-center gap-2">
                    {{ $field['name'] }} (Typ: {{ $field['type'] }})
                    <button
                        wire:click="removeField({{ $index }})"
                        class="filament-button filament-button--danger"
                    >
                        Entfernen
                    </button>
                </li>
            @endforeach
        </ul>
    @endif --}}
</div>



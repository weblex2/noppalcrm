@php
use App\Models\Dummy;
use App\Filament\Resources\DummyResource\Pages\EditDummy;
@endphp

<x-filament::page>
      <!-- Loader Overlay -->
    <div wire:loading wire:target="selectedResource"
        class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="text-white text-2xl font-bold animate-pulse">
            Lädt Resource...
        </div>
    </div>
    
    <div class="mb-4 w-1/2">
        {{ $this->form }}
        <br/>
        <x-filament::button
        :disabled="in_array($selectedResource, $availableResources)"
        wire:click="createResource"
    >
        Resource erstellen
    </x-filament::button>
    </div>

    @if($activeResource)
        <h2 class="text-lg font-bold mt-4">Resource: {{ $activeResource }}</h2>

        @livewire(\App\Filament\Resources\DummyResource\Pages\CreateDummy::class,
                ['resourceName' => $activeResource],
                 key($activeResource))
    @else
        <p>No active Resource</p>
    @endif
</x-filament::page>

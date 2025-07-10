<x-filament::card>
    <div class="py-4">
    <x-filament::button
                color="primary"
                wire:click="rebuildAllResources()"
                size="sm"
                icon="heroicon-m-arrow-path"
            >
                Rebuild all Resources
    </x-filament::button>
    </div>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @foreach ($resources as $resource)
        <div class="filament-card p-4 rounded-lg shadow border border-gray-200">
            <h2 class="text-lg font-bold mb-2">{{ class_basename($resource['class']) }}</h2>
            <p class="text-sm text-gray-600 mb-4">Model: {{ $resource['model'] }}</p>
            <p class="text-sm text-gray-600 mb-4">Model: {{ class_basename($resource['class']) }}</p>



            <x-filament::button
                color="danger"
                wire:click="nukeResource('{{ class_basename($resource['class']) }}')"
                size="sm"
                icon="heroicon-o-archive-box-x-mark"
            >
                Nuke
            </x-filament::button>

            <x-filament::button
                color="primary"
                wire:click="rebuildResource('{{ class_basename($resource['class']) }}')"
                size="sm"
                icon="heroicon-m-arrow-path"  {{-- Icon-Klasse --}}            >
                Rebuild
            </x-filament::button>

            @if ($commandOutput)
                <div class="bg-gray-100 border border-gray-300 p-4 rounded mt-4 whitespace-pre-wrap font-mono text-sm">
                    {!! nl2br(e($commandOutput)) !!}
                </div>
            @endif


        </div>
    @endforeach
</div>
</x-filament::card>




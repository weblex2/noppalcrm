<x-filament::card>
    <div class="py-4">
    <x-filament::button
                color="primary"
                wire:click="rebuildAllRelationManagers()"
                size="sm"
                icon="heroicon-m-arrow-path"
            >
                Rebuild all RelationManagers
    </x-filament::button>
    </div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    {{-- @if (session()->has('message'))
        <div class="mt-4 text-green-600 font-semibold">
            {{ session('message') }}
        </div>
    @endif --}}
    @foreach ($relationManagers as $resource)
        <div class="filament-card p-4 rounded-lg shadow border border-gray-200">
            <h2 class="text-lg font-bold mb-2">{{ class_basename($resource['class']) }}</h2>
            <p class="text-sm text-gray-600 mb-4">Resource: {{ $resource['resource'] }}</p>
            <p class="text-sm text-gray-600 mb-4">Name: {{ class_basename($resource['name']) }}</p>



            <x-filament::button
                color="danger"
                wire:click="deleteRelationManager('{{ class_basename($resource['class']) }}','{{$resource['resource']}}')"
                size="sm"
                icon="heroicon-o-archive-box-x-mark"
            >
                Delete
            </x-filament::button>

            <x-filament::button
                color="primary"
                wire:click="rebuildRelationManager('{{ class_basename($resource['class']) }}','{{$resource['resource']}}')"
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




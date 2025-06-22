<x-filament::page>
    <div x-data="{ tab: '{{ $this->logs[0]['name'] ?? '' }}' }">
        <div class="flex flex-wrap gap-2 border-b border-gray-200">
            @foreach ($this->logs as $log)
                <button
                    @click="tab = '{{ $log['name'] }}'"
                    :class="tab === '{{ $log['name'] }}' ? 'border-b-2 border-primary-500 text-primary-600' : 'text-gray-600'"
                    class="px-4 py-2 text-sm font-medium"
                >
                    {{ $log['name'] }}
                </button>
            @endforeach
        </div>

        @foreach ($this->logs as $log)
            <div x-show="tab === '{{ $log['name'] }}'" class="mt-4 space-y-4">
                <div class="flex justify-between items-center">
                    <h2 class="text-lg font-semibold">{{ $log['name'] }}</h2>
                    <form wire:submit.prevent="deleteLog('{{ $log['name'] }}')">
                        <x-filament::button color="danger" type="submit">
                            Löschen
                        </x-filament::button>
                    </form>
                </div>

                <div class="font-mono text-sm  p-4 rounded max-h-[600px] overflow-auto whitespace-pre-wrap break-words [overflow-wrap:anywhere]">
                    {!! nl2br(e($log['content'])) !!}
                </div>
            </div>
        @endforeach

        @if (empty($this->logs))
            <div class="text-gray-500 mt-4">Keine Logs gefunden.</div>
        @endif
    </div>
</x-filament::page>

<div>
    <x-filament::card>
    <form wire:submit.prevent="createRelationManager">
        {{ $this->form }}

        <x-filament::button type="submit" color="primary">
            Create RelationManager
        </x-filament::button>
    </form>
    @if (session()->has('success'))
        <p class="mt-4 font-medium text-success-600">
            {!! session('success') !!}
        </p>
    @endif

    @if (session()->has('error'))
        <p class="mt-4 font-medium text-danger-600">
            {!! session('error') !!}
        </p>
    @endif
     </x-filament::card>
</div>

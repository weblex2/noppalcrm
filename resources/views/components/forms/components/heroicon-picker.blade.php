<x-filament::input.wrapper
    :id="$getId()"
    :label="$getLabel()"
    :label-sr-only="$isLabelHidden()"
    :required="$isRequired()"
    :state-path="$getStatePath()"
>
    <x-filament::input.select
        :id="$getId()"
        :name="$getName()"
        wire:model.live="$getStatePath()"
        :disabled="$isDisabled()"
        {{ $attributes->merge([
            'class' => 'w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50',
        ]) }}
    >
        @foreach ($getIcons() as $icon)
            <option value="{{ $icon }}" @selected($getState() === $icon)>{{ $icon }}</option>
        @endforeach
    </x-filament::input.select>

    @if ($getState())
        <div class="mt-2 flex items-center gap-2 text-sm text-gray-600">
            <x-dynamic-component :component="$getState()" class="w-5 h-5" />
            <span>{{ $getState() }}</span>
        </div>
    @endif
</x-filament::input.wrapper>

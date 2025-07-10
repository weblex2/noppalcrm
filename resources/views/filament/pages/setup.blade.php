<x-filament::page>
    <h2 class="mb-4 text-xl font-bold">Ceate new Ressource</h2>
    @livewire('resource-creator')

    <h2 class="mb-4 text-xl font-bold">Create new Relation</h2>
    @livewire('relation-manager-creator')

    <h2 class="mb-4 text-xl font-bold">Edit Ressource</h2>
    @livewire('filament-resources-list')

    <h2 class="mb-4 text-xl font-bold">Edit Relation</h2>
    @livewire('filament-relation-manager-list')
</x-filament::page>

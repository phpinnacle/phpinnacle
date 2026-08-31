@if (count($entries) > 0)
<x-filament::dropdown
    class="fi-topbar-recent-entries"
    placement="bottom-end"
    :flip="true"
    :teleport="true"
>
    <x-slot name="trigger" >
        <x-filament::icon-button
            :color="$color"
            :icon="$icon"
            icon-size="lg"
            :label="__('phpinnacle-recens::messages.label')"
            class="fi-topbar-recent-entries-trigger"
        />
    </x-slot>
    <x-filament::dropdown.list>
        @foreach ($entries as $entry)
        <x-filament::dropdown.list.item tag="a" :href="$entry->getUrl()" :icon="$entry->getIcon()">
            {{ $entry->getLabel() }}
        </x-filament::dropdown.list.item>
        @endforeach
    </x-filament::dropdown.list>
</x-filament::dropdown>
@endif

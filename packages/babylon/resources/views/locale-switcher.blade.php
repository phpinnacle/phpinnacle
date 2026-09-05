<x-filament::dropdown
    class="fi-locale-switcher"
    placement="bottom-end"
    :flip="true"
    :teleport="true"
>
    <x-slot name="trigger">
        <x-filament::button color="gray">{{ strtoupper($current) }}</x-filament::button>
    </x-slot>
    <x-filament::dropdown.list>
        @foreach ($locales as $code => $name)
            <x-filament::dropdown.list.item
                :href="route('phpinnacle-babylon.switch-language', ['code' => $code, 'panel' => $panel])"
                tag="a"
            >
                <span class="fi-dropdown-list-item-label">
                    <span style="{{ $current === $code ? 'font-weight: 600;' : '' }}">
                        {{ $name }}
                    </span>
                </span>
            </x-filament::dropdown.list.item>
        @endforeach
    </x-filament::dropdown.list>
</x-filament::dropdown>

<div>
    @if (count($entries) > 0)
        <x-filament::dropdown
            class="fi-topbar-recent-entries"
            placement="bottom-end"
            :flip="true"
            :teleport="true"
            width="sm"
        >
            <x-slot name="trigger">
                <x-filament::icon-button
                    :color="$color"
                    :icon="$icon"
                    icon-size="lg"
                    :label="__('phpinnacle-recens::messages.label')"
                    class="fi-topbar-recent-entries-trigger"
                />
            </x-slot>

            @foreach ([true, false] as $pinned)
                @php
                    $section = array_filter($entries, fn ($entry) => ($entry->pinned_at !== null) === $pinned);
                @endphp
                @if (count($section) > 0)
                    <x-filament::dropdown.header>
                        {{ __($pinned ? 'phpinnacle-recens::messages.pinned' : 'phpinnacle-recens::messages.label') }}
                    </x-filament::dropdown.header>
                    <x-filament::dropdown.list>
                        @foreach ($section as $entry)
                            <div wire:key="recent-{{ $entry->id }}" style="display: flex; align-items: center; gap: 0.25rem">
                                <x-filament::dropdown.list.item tag="a" :href="$entry->getUrl()" :icon="$entry->getIcon()" style="flex: 1; min-width: 0">
                                    {{ $entry->getLabel() }}
                                </x-filament::dropdown.list.item>
                                <x-filament::icon-button
                                    :icon="$pinned ? 'heroicon-s-star' : 'heroicon-o-star'"
                                    :label="__($pinned ? 'phpinnacle-recens::messages.unpin' : 'phpinnacle-recens::messages.pin', ['title' => $entry->getLabel()])"
                                    color="gray"
                                    wire:click="togglePin('{{ $entry->id }}')"
                                />
                                <x-filament::icon-button
                                    icon="heroicon-o-x-mark"
                                    :label="__('phpinnacle-recens::messages.remove', ['title' => $entry->getLabel()])"
                                    color="gray"
                                    wire:click="remove('{{ $entry->id }}')"
                                />
                            </div>
                        @endforeach
                    </x-filament::dropdown.list>
                @endif
            @endforeach

            @if (count(array_filter($entries, fn ($entry) => $entry->pinned_at === null)) > 0)
                <x-filament::dropdown.list>
                    <x-filament::dropdown.list.item
                        icon="heroicon-o-trash"
                        color="danger"
                        wire:click="clear"
                        wire:confirm="{{ __('phpinnacle-recens::messages.confirm_clear') }}"
                    >
                        {{ __('phpinnacle-recens::messages.clear') }}
                    </x-filament::dropdown.list.item>
                </x-filament::dropdown.list>
            @endif
        </x-filament::dropdown>
    @endif
</div>

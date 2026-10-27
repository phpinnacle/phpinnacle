@php
    use Filament\Support\Facades\FilamentAsset;

    $items = $getItems();
    $id = $getId();
    $add = $getAction($getAddActionName());
    $delete = $getAction($getDeleteActionName());
    $reorder = $getAction($getReorderActionName());
    $available = $isAddable() ? $getBlockPickerBlocks() : [];
    $invalid = [];
    foreach ($items as $key => $item) {
        if ($errors->has($item->getStatePath() . '.*')) {
            $invalid[] = (string) $key;
        }
    }
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        {{ $getExtraAttributeBag()->class(['tabbed-builder', 'is-vertical' => $isVertical()]) }}
        data-field-interactive
        data-tabbed-builder
        x-load
        x-load-src="{{ FilamentAsset::getAlpineComponentSrc('tabbed-builder', 'phpinnacle/common') }}"
        x-data="tabbedBuilder(@js(['context' => $reorder->getContext(), 'reorderAction' => $getReorderActionName(), 'reorderable' => $isReorderable(), 'vertical' => $isVertical()]))"
    >
        <span hidden x-ref="metadata" data-keys="{{ json_encode(array_map('strval', array_keys($items))) }}"></span>
        <div class="tabbed-builder-header">
            <div
                class="tabbed-builder-tabs"
                x-ref="tabs"
                role="tablist"
                aria-label="{{ $getLabel() }}"
                aria-orientation="{{ $isVertical() ? 'vertical' : 'horizontal' }}"
                @if ($isReorderable())
                    x-sortable
                    x-on:end.stop="reorder($event)"
                @endif
            >
                @foreach ($items as $key => $item)
                    @php
                        $block = $item->getParentComponent();
                        $label = $block->getLabel($item->getRawState(), (string) $key, $loop->index);
                    @endphp
                    <div
                        class="tabbed-builder-tab"
                        x-bind:class="{ 'is-active': active === @js((string) $key) }"
                        x-sortable-item="{{ $key }}"
                        wire:key="{{ $id }}-tab-{{ $key }}"
                        role="presentation"
                    >
                        <button
                            type="button"
                            class="tabbed-builder-tab-button"
                            role="tab"
                            id="{{ $id }}-tab-{{ $key }}"
                            aria-controls="{{ $id }}-panel-{{ $key }}"
                            aria-invalid="{{ in_array((string) $key, $invalid, true) ? 'true' : 'false' }}"
                            x-bind:aria-selected="active === @js((string) $key)"
                            x-bind:tabindex="active === @js((string) $key) ? 0 : -1"
                            x-on:click="select(@js((string) $key))"
                            x-on:keydown="keydown($event, @js((string) $key))"
                            @if ($isReorderable()) x-sortable-handle @endif
                        >
                            @if ($isReorderable())
                                <x-filament::icon icon="heroicon-m-bars-3" class="tabbed-builder-drag" />
                            @endif
                            @if ($icon = $block->getIcon())
                                <x-filament::icon :icon="$icon" />
                            @endif
                            <span class="tabbed-builder-label">{{ $label }}</span>
                            @if (in_array((string) $key, $invalid, true))
                                <x-filament::icon icon="heroicon-m-exclamation-circle" class="tabbed-builder-error" :title="__('phpinnacle-common::tabbed-builder.invalid')" />
                            @endif
                        </button>
                        @if ($isDeletable())
                            {{ $delete(['item' => (string) $key])->label(__('phpinnacle-common::tabbed-builder.remove', ['block' => $label]))->extraAttributes(['class' => 'tabbed-builder-remove']) }}
                        @endif
                    </div>
                @endforeach
            </div>
            @if ($available !== [])
                <div class="tabbed-builder-add">
                    <x-filament::dropdown placement="bottom-start" teleport>
                        <x-slot name="trigger">
                            <x-filament::button color="gray" icon="heroicon-m-plus" size="sm" :aria-label="$getAddActionLabel()">
                                @if ($isVertical() || $items === [])
                                    {{ $getAddActionLabel() }}
                                @endif
                            </x-filament::button>
                        </x-slot>
                        <x-filament::dropdown.list>
                            @foreach ($available as $block)
                                {{ $add(['block' => $block->getName()])->livewireClickHandlerEnabled()->grouped()->label($block->getLabel())->icon($block->getIcon()) }}
                            @endforeach
                        </x-filament::dropdown.list>
                    </x-filament::dropdown>
                </div>
            @endif
        </div>
        <div class="tabbed-builder-panels">
            @forelse ($items as $key => $item)
                <div
                    class="tabbed-builder-panel"
                    role="tabpanel"
                    id="{{ $id }}-panel-{{ $key }}"
                    aria-labelledby="{{ $id }}-tab-{{ $key }}"
                    tabindex="0"
                    x-cloak
                    x-show="active === @js((string) $key)"
                    x-on:expand.stop="select(@js((string) $key))"
                    wire:key="{{ $id }}-panel-{{ $key }}"
                >
                    {{ $item }}
                </div>
            @empty
                <p class="tabbed-builder-empty">{{ __('phpinnacle-common::tabbed-builder.empty') }}</p>
            @endforelse
        </div>
        @if ($isReorderable() && count($items) > 1)
            <p class="tabbed-builder-hint">{{ __('phpinnacle-common::tabbed-builder.reorder') }}</p>
        @endif
    </div>
</x-dynamic-component>

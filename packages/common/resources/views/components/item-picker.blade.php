@php
    use Filament\Support\Facades\FilamentAsset;
    use PHPinnacle\Common\Forms\Item;
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-load
        x-load-src="{{ FilamentAsset::getAlpineComponentSrc('item-picker', package: 'phpinnacle/common') }}"
        x-data="itemPicker({
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$getStatePath()}')") }},
            nodes: @js($nodes),
            select: @js($select),
            disabled: @js($disabled),
            multiple: @js($multiple),
        })"
        x-effect="nodes = @js($nodes); disabled = @js($disabled)"
        x-on:resize.window="position()"
        x-on:scroll.window.capture="position()"
        {{ $getExtraAttributeBag()
            ->merge(['wire:key' => $id . '-picker-' . ($multiple ? 'multiple' : 'single')])
            ->class([
                'common-item-picker',
                'common-item-picker--compact' => $isCompact(),
                'common-item-picker--dense' => $isDense(),
                'common-item-picker--circular' => $isCircular(),
                'common-item-picker--multiple' => $multiple,
            ]) }}
    >
        @if ($select)
            <x-filament::input.wrapper :disabled="$disabled" :valid="!$errors->has($getStatePath())" class="common-item-picker__trigger-wrapper">
                <button
                    type="button"
                    id="{{ $id }}"
                    x-ref="trigger"
                    x-on:click="toggle()"
                    x-on:keydown.arrow-down.prevent="if (!open) toggle()"
                    x-on:keydown.arrow-up.prevent="if (!open) toggle()"
                    aria-haspopup="tree"
                    aria-controls="{{ $id }}-items"
                    x-bind:aria-expanded="open"
                    aria-labelledby="{{ $id }}-label"
                    @disabled($disabled)
                    class="common-item-picker__trigger"
                >
                    <span x-ref="value" class="common-item-picker__value">
                        @foreach ($rows as $row)
                            @if ($row['node'] instanceof Item)
                                <span
                                    x-cloak
                                    x-show="selected(@js($row['node']->value))"
                                    data-selected-value="{{ $row['node']->value }}"
                                    x-bind:class="{ 'is-hidden': multiple && !visibleValues.includes(@js($row['node']->value)) }"
                                    x-bind:aria-hidden="multiple && !visibleValues.includes(@js($row['node']->value))"
                                    class="common-item-picker__content"
                                    style="{{ \Filament\Support\get_color_css_variables($row['node']->color, [50, 400, 600]) }}"
                                >
                                    @include('phpinnacle-common::components.item-picker-content', [
                                        'item' => $row['node'],
                                        'showDescription' => !$multiple,
                                        'showBadges' => false,
                                        'descriptionId' => null,
                                        'badgesId' => null,
                                    ])
                                </span>
                            @endif
                        @endforeach
                        <span x-show="!selectedNodes().length" class="common-item-picker__placeholder" x-text="selectionText() || @js(__('phpinnacle-common::item-picker.placeholder'))"></span>
                        @if ($multiple)
                            <span
                                x-cloak
                                x-ref="overflow"
                                x-bind:class="{ 'is-hidden': !overflowCount }"
                                x-bind:aria-hidden="!overflowCount"
                                x-bind:title="overflowLabels.join(', ')"
                                x-text="'+' + overflowCount"
                                class="common-item-picker__overflow"
                            ></span>
                        @endif
                    </span>
                    <x-filament::icon icon="heroicon-m-chevron-up-down" class="common-item-picker__chevron" />
                </button>
            </x-filament::input.wrapper>
        @endif

        <div
            id="{{ $id }}-items"
            x-ref="panel"
            role="tree"
            aria-multiselectable="{{ $multiple ? 'true' : 'false' }}"
            aria-label="{{ $getLabel() }}"
            @if ($select)
                popover="auto"
                x-bind:style="panelStyle"
                x-on:toggle="open = $event.newState === 'open'"
                x-on:keydown.tab="close()"
            @endif
            @class(['common-item-picker__items', 'common-item-picker__popup' => $select])
        >
            @forelse ($rows as $row)
                @php $item = $row['node']; @endphp
                @if ($item instanceof Item)
                    @include('phpinnacle-common::components.item-picker-item', ['item' => $item, 'row' => $row])
                @else
                    <div
                        wire:key="{{ $id }}-group{{ $row['key'] }}"
                        x-show="visible({ ancestors: @js($row['ancestors']) })"
                        role="presentation"
                        class="common-item-picker__group"
                    >
                        <span class="common-item-picker__heading">
                            <span>{{ $item->label }}</span>
                            @if ($item->badges !== [])
                                @include('phpinnacle-common::components.item-picker-badges', ['badges' => $item->badges, 'badgesId' => null])
                            @endif
                        </span>
                        @if ($item->description !== null)
                            @if ($isCompact())
                                <span aria-hidden="true">·</span>
                            @endif
                            <p>{{ $item->description }}</p>
                        @endif
                    </div>
                @endif
            @empty
                <p class="common-item-picker__placeholder">{{ __('phpinnacle-common::item-picker.empty') }}</p>
            @endforelse
            @if ($disabled && !$select)
                <span x-show="!selectedNodes().length" class="common-item-picker__placeholder" x-text="selectionText() || '—'"></span>
            @endif
        </div>
    </div>
</x-dynamic-component>

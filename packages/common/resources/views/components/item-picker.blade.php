@php
    use Filament\Support\Facades\FilamentAsset;
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-load
        x-load-src="{{ FilamentAsset::getAlpineComponentSrc('item-picker', package: 'phpinnacle/common') }}"
        x-data="itemPicker({
            ...@js($pickerData),
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$getStatePath()}')") }},
            fetchItems: (search, offset, state) => $wire.callSchemaComponentMethod(@js($getKey()), 'getItemsForJs', { search, offset, state }),
            fetchSelection: (state) => $wire.callSchemaComponentMethod(@js($getKey()), 'getSelectionForJs', { state }),
            select: @js($select),
            multiple: @js($multiple),
        })"
        x-effect="$nextTick(() => sync(@js($pickerData)))"
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
            @include('phpinnacle-common::components.item-picker-trigger')
        @endif

        <button x-cloak x-show="selectionError" type="button" x-on:click="refreshSelection()" class="common-item-picker__placeholder">{{ __('phpinnacle-common::item-picker.retry') }}</button>

        @include('phpinnacle-common::components.item-picker-panel')
    </div>
</x-dynamic-component>

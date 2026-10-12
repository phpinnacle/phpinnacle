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
            <span wire:ignore class="common-item-picker__selection" x-html="selectionHtml">
                <template>{!! $pickerData['selectionHtml'] !!}</template>
            </span>
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

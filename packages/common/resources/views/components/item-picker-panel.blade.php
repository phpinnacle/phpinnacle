<div
    id="{{ $id }}-panel"
    x-ref="panel"
    x-on:scroll="loadMore()"
    x-bind:aria-busy="loading"
    @if ($select)
        popover="auto"
        x-bind:style="panelStyle"
        x-on:toggle="open = $event.newState === 'open'"
        x-on:keydown.tab="close()"
    @endif
    @class(['common-item-picker__panel', 'common-item-picker__popup' => $select, 'common-item-picker__panel--limited' => $getLimit() !== null])
>
    @if ($pickerData['searchable'])
        <x-filament::input.wrapper class="common-item-picker__search">
            <x-filament::input
                type="search"
                x-ref="search"
                x-model="search"
                x-on:input="scheduleSearch()"
                x-on:keydown.arrow-down.prevent="focus(navigable()[0]?.key)"
                x-on:keydown.escape.prevent="if (select) close()"
                aria-label="{{ __('phpinnacle-common::item-picker.search') }}"
                placeholder="{{ __('phpinnacle-common::item-picker.search') }}"
                :disabled="$disabled"
            />
        </x-filament::input.wrapper>
    @endif
    <div
        id="{{ $id }}-items"
        role="tree"
        aria-multiselectable="{{ $multiple ? 'true' : 'false' }}"
        aria-label="{{ $getLabel() }}"
        class="common-item-picker__items"
    >
        @if (!$select && !$disabled && $pickerData['hasResolver'])
            <div wire:ignore x-show="visibleInlineNodes().length" x-html="inlineHtml" class="common-item-picker__resolved">
                <template>{!! $pickerData['selectionHtml'] !!}</template>
            </div>
        @endif
        <div wire:ignore x-html="itemsHtml" class="common-item-picker__items">
            <template>{!! $pickerData['html'] !!}</template>
        </div>
    </div>
    <p x-show="!nodes.length && !loading && !error" class="common-item-picker__placeholder">{{ __('phpinnacle-common::item-picker.empty') }}</p>
    <p x-cloak x-show="loading" role="status" class="common-item-picker__placeholder">{{ __('phpinnacle-common::item-picker.loading') }}</p>
    <button x-cloak x-show="error" type="button" x-on:click="load(errorOffset)" class="common-item-picker__placeholder">{{ __('phpinnacle-common::item-picker.retry') }}</button>
    @if ($disabled && !$select)
        <span x-show="!selectedNodes().length" class="common-item-picker__placeholder" x-text="selectionText() || '—'"></span>
    @endif
</div>

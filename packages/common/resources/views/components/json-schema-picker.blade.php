@props(['schema', 'value', 'choose', 'label', 'disabled' => false, 'clearable' => true, 'format' => false, 'properties' => true, 'required' => false, 'constraints' => false, 'compact' => false, 'searchable' => true, 'icons' => [], 'colors' => true, 'descriptions' => true, 'propertyTitles' => []])

<div
    x-load
    x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('json-schema-tools', package: 'phpinnacle/common') }}"
    x-data="jsonSchemaTools()"
    x-effect="schemaSelectedKey = JSON.stringify({{ $value }}); schemaSyncSelection()"
    {{ $attributes->class(['common-json-schema-picker']) }}
>
    @include('phpinnacle-common::components.json-schema-options')
    <template x-effect="schemaData = schemaBuildGroups({{ $schema }}, {{ $label }})"></template>
    <x-filament::dropdown :menu="false" placement="bottom-start" :shift="true" :teleport="true" width="fi-width-none common-json-schema-picker__floating" class="common-json-schema-picker__dropdown" x-init="$nextTick(() => matchDropdownWidth($refs.panel, $el))">
        <x-slot name="trigger" class="common-json-schema-picker__input">
            <x-filament::input.wrapper class="common-json-schema-picker__input">
                <div class="common-json-schema-picker__control">
                    <button type="button" @disabled($disabled) aria-haspopup="dialog" :aria-expanded="isOpen" :aria-label="{{ $label }}" class="common-json-schema-picker__trigger">
                        <span class="common-json-schema-picker__selection" :class="{ 'common-json-schema-picker__selection--placeholder': {{ $value }} === null }">
                            <template x-if="schemaSelectedSource !== null">
                                <span x-data="{ get source() { return schemaSelectedSource } }" class="common-json-schema-picker__selected-field">
                                    <x-phpinnacle-common::json-schema-field name="source.fieldLabel" properties="schemaProperties" :description="false" :constraints="false" />
                                </span>
                            </template>
                            <template x-if="schemaSelectedSource === null">
                                <span x-text="schemaSelectionLabel({{ $value }}, @js(__('phpinnacle-common::json-schema.placeholder')))"></span>
                            </template>
                        </span>
                        <x-filament::icon icon="heroicon-o-chevron-up-down" class="common-json-schema-picker__icon" />
                    </button>
                    @if ($clearable && !$disabled)
                        <x-filament::icon-button
                            icon="heroicon-o-x-mark"
                            color="gray"
                            size="sm"
                            :label="__('phpinnacle-common::json-schema.clear')"
                            x-cloak
                            x-show="{{ $value }} !== null"
                            x-on:mousedown.stop
                            x-on:keyup.enter.stop
                            x-on:keyup.space.stop
                            x-on:click.stop="{{ $value }} = null; close()"
                            class="common-json-schema-picker__clear"
                        />
                    @endif
                </div>
            </x-filament::input.wrapper>
        </x-slot>
        <template x-if="isOpen">
            <div class="common-json-schema-picker__panel">
                <x-phpinnacle-common::json-schema-tree
                    groups="schemaData"
                    selected="source.key === schemaSelectedKey"
                    :choose="$choose . '; close(); return true'"
                    properties="schemaProperties"
                    descriptions="schemaDescriptions"
                    compact="schemaCompact"
                    searchable="schemaSearchable"
                    :disabled="$disabled"
                />
            </div>
        </template>
    </x-filament::dropdown>
</div>

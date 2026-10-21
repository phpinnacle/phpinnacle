@props(['schema', 'value', 'choose', 'label', 'disabled' => false, 'clearable' => true, 'format' => false, 'properties' => true, 'required' => false, 'constraints' => false, 'compact' => false, 'searchable' => true, 'icons' => [], 'colors' => true, 'descriptions' => true, 'propertyTitles' => [], 'selectArrayItemFields' => false])

<div
    x-load
    x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('json-schema-tools', package: 'phpinnacle/eidos') }}"
    x-data="jsonSchemaTools()"
    x-effect="schemaSelectedKey = {{ $value }}; schemaSyncSelection()"
    {{ $attributes->class(['eidos-json-schema-picker']) }}
>
    @include('phpinnacle-eidos::components.json-schema-options')
    <template x-effect="schemaData = schemaBuildGroups({{ $schema }}, {{ $label }})"></template>
    <x-filament::dropdown :menu="false" placement="bottom-start" :shift="true" :teleport="true" width="fi-width-none eidos-json-schema-picker__floating" class="eidos-json-schema-picker__dropdown" x-init="$nextTick(() => matchDropdownWidth($refs.panel, $el))">
        <x-slot name="trigger" class="eidos-json-schema-picker__input">
            <x-filament::input.wrapper class="eidos-json-schema-picker__input">
                <div class="eidos-json-schema-picker__control">
                    <button type="button" @disabled($disabled) aria-haspopup="dialog" :aria-expanded="isOpen" :aria-label="{{ $label }}" class="eidos-json-schema-picker__trigger">
                        <span class="eidos-json-schema-picker__selection" :class="{ 'eidos-json-schema-picker__selection--placeholder': {{ $value }} === null }">
                            <template x-if="schemaSelectedSource !== null">
                                <span x-data="{ get source() { return schemaSelectedSource } }" class="eidos-json-schema-picker__selected-field">
                                    <x-phpinnacle-eidos::json-schema-field name="source.fieldLabel" properties="schemaProperties" :description="false" :constraints="false" />
                                </span>
                            </template>
                            <template x-if="schemaSelectedSource === null">
                                <span x-text="schemaSelectionLabel({{ $value }}, @js(__('phpinnacle-eidos::json-schema.placeholder')))"></span>
                            </template>
                        </span>
                        <x-filament::icon icon="heroicon-o-chevron-up-down" class="eidos-json-schema-picker__icon" />
                    </button>
                    @if ($clearable && !$disabled)
                        <x-filament::icon-button
                            icon="heroicon-o-x-mark"
                            color="gray"
                            size="sm"
                            :label="__('phpinnacle-eidos::json-schema.clear')"
                            x-cloak
                            x-show="{{ $value }} !== null"
                            x-on:mousedown.stop
                            x-on:keyup.enter.stop
                            x-on:keyup.space.stop
                            x-on:click.stop="{{ $value }} = null; close()"
                            class="eidos-json-schema-picker__clear"
                        />
                    @endif
                </div>
            </x-filament::input.wrapper>
        </x-slot>
        <div class="eidos-json-schema-picker__panel">
            <x-phpinnacle-eidos::json-schema-tree
                x-init="$watch('isOpen', open => { if (!open) { jsonSchemaSearch = ''; jsonSchemaExpanded = {}; $el.parentElement.scrollTop = 0 } })"
                groups="schemaData"
                selected="source.value === schemaSelectedKey"
                :choose="$choose . '; close(); return true'"
                properties="schemaProperties"
                descriptions="schemaDescriptions"
                compact="schemaCompact"
                searchable="schemaSearchable"
                :disabled="$disabled"
            />
        </div>
    </x-filament::dropdown>
</div>

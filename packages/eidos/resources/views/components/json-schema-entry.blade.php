@props(['schema', 'label', 'value' => 'null', 'inline' => false, 'format' => false, 'properties' => true, 'required' => false, 'constraints' => false, 'compact' => false, 'searchable' => false, 'icons' => [], 'colors' => true, 'descriptions' => true, 'propertyTitles' => []])

<div
    x-load
    x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getScriptSrc('resources/js/json-schema-tools', package: 'phpinnacle/eidos') }}"
    x-data="jsonSchemaTools()"
    x-effect="schemaSelectedKey = {{ $value }}"
    {{ $attributes->class(['eidos-json-schema-entry']) }}
>
    @include('phpinnacle-eidos::components.json-schema-options')
    <template x-effect="schemaData = schemaBuildGroups({{ $schema }}, {{ $label }})"></template>
    @if ($inline)
        <x-phpinnacle-eidos::json-schema-tree
            groups="schemaData"
            selected="source.value === schemaSelectedKey"
            properties="schemaProperties"
            descriptions="schemaDescriptions"
            compact="schemaCompact"
            searchable="schemaSearchable"
            :disabled="true"
        />
    @else
        <div class="eidos-json-schema-entry__summary">
            <span class="eidos-json-schema-entry__selection">
                <template x-if="schemaSelectedSource !== null">
                    <span x-data="{ get source() { return schemaSelectedSource } }" class="eidos-json-schema-entry__selected-field">
                        <x-phpinnacle-eidos::json-schema-field name="source.fieldLabel" properties="schemaProperties" :description="false" :constraints="false" />
                    </span>
                </template>
                <template x-if="schemaSelectedSource === null">
                    <span class="eidos-json-schema-entry__placeholder" x-text="schemaSelectionLabel({{ $value }}, @js(__('phpinnacle-eidos::json-schema.no_selection')))"></span>
                </template>
            </span>
            <x-filament::dropdown :menu="false" placement="bottom-end" :shift="true" :teleport="true" width="fi-width-none eidos-json-schema-entry__floating" class="eidos-json-schema-entry__dropdown" x-init="$nextTick(() => matchDropdownWidth($refs.panel, $el.parentElement))">
                <x-slot name="trigger">
                    <x-filament::icon-button
                        icon="heroicon-o-magnifying-glass"
                        color="gray"
                        size="sm"
                        :label="__('phpinnacle-eidos::json-schema.view_schema')"
                        aria-haspopup="dialog"
                        x-bind:aria-expanded="isOpen"
                    />
                </x-slot>
                <div role="dialog" aria-label="{{ __('phpinnacle-eidos::json-schema.view_schema') }}" class="eidos-json-schema-entry__panel">
                    <x-phpinnacle-eidos::json-schema-tree
                        x-init="$watch('isOpen', open => { if (!open) { jsonSchemaSearch = ''; jsonSchemaExpanded = {}; $el.parentElement.scrollTop = 0 } })"
                        groups="schemaData"
                        selected="source.value === schemaSelectedKey"
                        properties="schemaProperties"
                        descriptions="schemaDescriptions"
                        compact="schemaCompact"
                        searchable="schemaSearchable"
                        :disabled="true"
                    />
                </div>
            </x-filament::dropdown>
        </div>
    @endif
</div>

@props(['schema', 'label', 'format' => false, 'properties' => true, 'required' => false, 'constraints' => false, 'compact' => false, 'searchable' => false, 'icons' => [], 'colors' => true, 'descriptions' => true, 'propertyTitles' => []])

<div
    x-load
    x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('json-schema-tools', package: 'phpinnacle/common') }}"
    x-data="jsonSchemaTools()"
    {{ $attributes }}
>
    @include('phpinnacle-common::components.json-schema-options')
    <x-phpinnacle-common::json-schema-tree
        :groups="'schemaBuildGroups(' . $schema . ', ' . $label . ')'"
        properties="schemaProperties"
        descriptions="schemaDescriptions"
        compact="schemaCompact"
        searchable="schemaSearchable"
        :disabled="true"
    />
</div>

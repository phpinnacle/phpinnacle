@php
    $definition = $getJsonSchema()->toArray();
    $propertyTitles = app(\PHPinnacle\Common\JsonSchema\PropertyTitles::class)->render($definition);
    $icons = $areIconsVisible() ? app(\PHPinnacle\Common\JsonSchema\Icons::class)->render($definition) : [];
@endphp

<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    <div
        x-data="{ schemaDocument: null }"
        x-effect="schemaDocument = @js($definition)"
    >
        <x-phpinnacle-common::json-schema-entry
            schema="schemaDocument"
            :label="\Illuminate\Support\Js::from($getLabel())"
            :format="$isFormatVisible()"
            :properties="$arePropertiesVisible()"
            :required="$isRequiredMarkerVisible()"
            :constraints="$areConstraintsVisible()"
            :compact="$isCompact()"
            :searchable="$isSearchable()"
            :icons="$icons"
            :colors="$areColorsVisible()"
            :descriptions="$areDescriptionsVisible()"
            :property-titles="$propertyTitles"
        />
    </div>
</x-dynamic-component>

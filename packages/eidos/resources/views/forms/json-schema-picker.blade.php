@php
    $definition = $getJsonSchema()->toArray();
    $propertyTitles = app(\PHPinnacle\Eidos\JsonSchema\PropertyTitles::class)->render($definition);
    $icons = $areIconsVisible() ? app(\PHPinnacle\Eidos\JsonSchema\Icons::class)->render($definition) : [];
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="{ schemaValue: $wire.{{ $applyStateBindingModifiers("\$entangle('{$getStatePath()}')") }}, schemaDocument: null }"
        x-effect="schemaDocument = @js($definition)"
        {{ $getExtraAttributeBag() }}
    >
        <x-phpinnacle-eidos::json-schema-picker
            schema="schemaDocument"
            value="schemaValue"
            choose="schemaValue = source.value"
            :label="\Illuminate\Support\Js::from($getLabel())"
            :disabled="$isDisabled()"
            :clearable="!$isRequired()"
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

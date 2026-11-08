<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    <div
        wire:key="{{ $getId() }}-schema-{{ md5(serialize([$definition, $getState(), $isInline()])) }}"
        x-data="{ schemaDocument: null, schemaValue: null }"
        x-effect="schemaDocument = @js($definition); schemaValue = @js($getState())"
    >
        <x-phpinnacle-eidos::json-schema-entry
            schema="schemaDocument"
            value="schemaValue"
            :inline="$isInline()"
            :label="\Illuminate\Support\Js::from($getLabel())"
            :format="$isFormatVisible()"
            :properties="$arePropertiesVisible()"
            :required="$isRequiredMarkerVisible()"
            :constraints="$areConstraintsVisible()"
            :compact="$isCompact()"
            :searchable="$isSearchable()"
            :icons="$areIconsVisible() ? $icons : false"
            :colors="$areColorsVisible()"
            :descriptions="$areDescriptionsVisible()"
            :property-titles="$propertyTitles"
        />
    </div>
</x-dynamic-component>

<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    <div
        x-data="{ schemaDocument: null }"
        x-effect="schemaDocument = @js($definition)"
    >
        <x-phpinnacle-eidos::json-schema-entry
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

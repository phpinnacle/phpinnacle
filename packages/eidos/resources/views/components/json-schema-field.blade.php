@props(['compact' => 'false', 'name' => 'jsonSchemaName(source)', 'properties' => 'true', 'selected' => null, 'description' => true, 'constraints' => true])

<span {{ $attributes->class(['eidos-json-schema-tree__content']) }} :class="{ 'eidos-json-schema-tree__content--icons': source.iconHtml }" :style="jsonSchemaColorStyles(source.color)">
    <span class="eidos-json-schema-tree__heading">
        <span x-show="source.iconHtml" class="eidos-json-schema-tree__field-icon" x-html="source.iconHtml"></span>
        <span class="eidos-json-schema-tree__names">
            <span class="eidos-json-schema-tree__name" :class="{ 'eidos-json-schema-tree__name--title': source.title }" x-text="source.title || {{ $name }}"></span>
            <span x-show="source.required" class="eidos-json-schema-tree__required">
                <span aria-hidden="true">*</span>
                <span class="fi-sr-only">{{ __('phpinnacle-eidos::json-schema.required') }}</span>
            </span>
            <span x-show="source.title && {{ $properties }}" class="eidos-json-schema-tree__property" x-text="{{ $name }}"></span>
        </span>
        <span class="eidos-json-schema-tree__types">
            @if ($constraints)
                <template x-if="{{ $compact }} && source.constraints.length">
                    <x-phpinnacle-eidos::json-schema-popover
                        tooltip="jsonSchemaConstraintsTooltip"
                        popover="jsonSchemaConstraintsPopover"
                        class="eidos-json-schema-tree__restrictions-icon"
                    >
                        <x-filament::icon icon="heroicon-o-adjustments-horizontal" aria-hidden="true" />
                    </x-phpinnacle-eidos::json-schema-popover>
                </template>
                <template x-for="(constraint, constraintIndex) in source.constraints" :key="constraintIndex">
                    <span class="eidos-json-schema-tree__constraints">
                        <span x-show="constraint.scope.length" class="eidos-json-schema-tree__scope">
                            <span x-text="constraint.scope.map(part => typeof part === 'number' ? @js(__('phpinnacle-eidos::json-schema.alternative')) + ' ' + part : part === 'items' ? @js(__('phpinnacle-eidos::json-schema.items')) : part).join(' → ')"></span>
                            <span class="eidos-json-schema-tree__scope-type" x-text="constraint.type"></span>
                        </span>
                        <span class="eidos-json-schema-tree__constraint-values">
                            <template x-for="value in constraint.values" :key="value.kind">
                                <span
                                    :aria-label="jsonSchemaConstraintTooltip(constraint, value, @js(__('phpinnacle-eidos::json-schema')))"
                                    role="img"
                                    class="eidos-json-schema-tree__constraint-chip"
                                    :class="{ 'eidos-json-schema-tree__constraint-chip--unique': value.kind === 'unique', 'eidos-json-schema-tree__constraint-chip--pattern': value.kind === 'pattern' }"
                                >
                                    <svg x-show="value.kind === 'length'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true">
                                        <rect x="3" y="7" width="18" height="10" rx="1.5" />
                                        <path d="M7 7v4m4-4v6m4-6v4m4-4v6" />
                                    </svg>
                                    <x-filament::icon icon="heroicon-o-bars-3" x-show="value.kind === 'items'" aria-hidden="true" />
                                    <span x-show="value.kind === 'unique'">{{ __('phpinnacle-eidos::json-schema.constraints.unique') }}</span>
                                    <span x-show="value.value !== ''" class="eidos-json-schema-tree__constraint-value" x-text="jsonSchemaConstraintValue(value)"></span>
                                </span>
                            </template>
                        </span>
                    </span>
                </template>
            @endif
            <span x-show="source.nullable" class="eidos-json-schema-tree__type eidos-json-schema-tree__type--null">null</span>
            <template x-if="!source.enums.length">
                <span class="eidos-json-schema-tree__type" :class="{ 'eidos-json-schema-tree__type--null': source.type === 'null' }" x-text="source.type"></span>
            </template>
            <template x-if="source.enums.length">
                <x-phpinnacle-eidos::json-schema-popover
                    tooltip="jsonSchemaEnumTooltip"
                    popover="jsonSchemaEnumPopover"
                    label="source.type + ': ' + tooltip"
                    x-text="source.type"
                    class="eidos-json-schema-tree__type eidos-json-schema-tree__type--enum"
                />
            </template>
        </span>
        @if ($selected !== null)
            <x-filament::icon icon="heroicon-o-check" class="eidos-json-schema-tree__check" x-show="{{ $selected }}" />
        @endif
    </span>
    @if ($description)
        <span x-show="{{ is_bool($description) ? 'true' : $description }} && source.description" class="eidos-json-schema-tree__description" x-text="source.description"></span>
    @endif
</span>

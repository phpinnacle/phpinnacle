@props(['groups', 'selected' => 'false', 'choose' => '', 'search' => 'null', 'disabled' => false, 'searchable' => 'true', 'compact' => 'false', 'properties' => 'true', 'descriptions' => 'true'])

@php
    $searchable = is_bool($searchable) ? \Illuminate\Support\Js::from($searchable) : $searchable;
    $descriptions = is_bool($descriptions) ? \Illuminate\Support\Js::from($descriptions) : $descriptions;
@endphp

<div
    x-load
    x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getScriptSrc('resources/js/json-schema-picker', package: 'phpinnacle/eidos') }}"
    x-data="jsonSchemaPicker({
        groups: () => {{ $groups }},
        selected: source => {{ $selected }},
        choose: source => { {{ $choose }} },
        search: () => {{ $search }},
        searchable: () => {{ $searchable }},
        disabled: @js($disabled),
    })"
    x-effect="jsonSchemaSync()"
    :class="{ 'eidos-json-schema-tree--compact': {{ $compact }} }"
    {{ $attributes->class(['eidos-json-schema-tree']) }}
>
    <div x-show="{{ $searchable }}" class="eidos-json-schema-tree__search">
        <x-filament::input.wrapper prefix-icon="heroicon-o-magnifying-glass">
            <x-filament::input type="search" x-model.debounce.150ms="jsonSchemaSearch" placeholder="{{ __('phpinnacle-eidos::json-schema.search') }}" aria-label="{{ __('phpinnacle-eidos::json-schema.search') }}" />
        </x-filament::input.wrapper>
    </div>
    <template x-effect="jsonSchemaSyncSelection()"></template>
    <template x-effect="jsonSchemaSyncSearch()"></template>
    <template x-for="group in jsonSchemaMatches" :key="group.key">
        <div class="eidos-json-schema-tree__rows">
            <template x-for="source in group.sources.filter(source => jsonSchemaVisible(source))" :key="source.key">
                <div
                    class="eidos-json-schema-tree__row"
                    :style="`--json-schema-depth: ${jsonSchemaSearching ? 0 : source.depth}`"
                    :class="{ 'eidos-json-schema-tree__row--selected': jsonSchemaSelected(source), 'eidos-json-schema-tree__row--expandable': @js($disabled) && source.hasChildren && !jsonSchemaSearching }"
                    :aria-current="jsonSchemaSelected(source) ? 'true' : null"
                    @if ($disabled)
                        :role="source.hasChildren && !jsonSchemaSearching ? 'button' : null"
                        :tabindex="source.hasChildren && !jsonSchemaSearching ? 0 : null"
                        :aria-label="source.hasChildren && !jsonSchemaSearching ? source.fieldLabel : null"
                        :aria-expanded="source.hasChildren && !jsonSchemaSearching ? jsonSchemaIsExpanded(source.key) : null"
                        x-on:click="source.hasChildren && !jsonSchemaSearching && jsonSchemaToggle(source)"
                        x-on:keydown.enter.prevent="source.hasChildren && !jsonSchemaSearching && jsonSchemaToggle(source)"
                        x-on:keydown.space.prevent="source.hasChildren && !jsonSchemaSearching && jsonSchemaToggle(source)"
                    @endif
                >
                    @if ($disabled)
                        <span aria-hidden="true" class="eidos-json-schema-tree__branch">
                            <x-filament::icon icon="heroicon-o-chevron-right" x-show="source.hasChildren && !jsonSchemaSearching" class="eidos-json-schema-tree__arrow" />
                        </span>
                    @else
                        <button type="button" x-show="source.hasChildren && !jsonSchemaSearching" x-on:click="jsonSchemaToggle(source)" :aria-label="@js(__('phpinnacle-eidos::json-schema.expand')) + ' ' + source.fieldLabel" :aria-expanded="jsonSchemaIsExpanded(source.key)" class="eidos-json-schema-tree__branch">
                            <x-filament::icon icon="heroicon-o-chevron-right" class="eidos-json-schema-tree__arrow" />
                        </button>
                    @endif
                    @if ($disabled)
                        <div :title="{{ $descriptions }} && source.description ? source.fieldLabel + '\n' + source.description : source.fieldLabel" class="eidos-json-schema-tree__value">
                    @else
                        <button type="button" x-on:click="jsonSchemaChoose(source)" :disabled="source.compatible === false && source.value !== null" :aria-disabled="source.compatible === false" :class="{ 'eidos-json-schema-tree__value--readonly': source.value === null }" :title="{{ $descriptions }} && source.description ? source.fieldLabel + '\n' + source.description : source.fieldLabel" :data-source="source.key" :aria-pressed="jsonSchemaSelected(source)" class="eidos-json-schema-tree__value">
                    @endif
                        <x-phpinnacle-eidos::json-schema-field
                            :compact="$compact"
                            :properties="$properties"
                            :description="$descriptions"
                            selected="jsonSchemaSelected(source)"
                        />
                    @if ($disabled)
                        </div>
                    @else
                        </button>
                    @endif
                </div>
            </template>
        </div>
    </template>
    <div x-show="!jsonSchemaMatches.length" class="eidos-json-schema-tree__empty">{{ $disabled ? __('phpinnacle-eidos::json-schema.empty') : __('phpinnacle-eidos::json-schema.no_matches') }}</div>
</div>

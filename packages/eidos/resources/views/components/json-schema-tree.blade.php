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
        labels: @js(__('phpinnacle-eidos::json-schema.composition')),
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
                <div>
                    <template x-if="source.composition">
                        <div class="eidos-json-schema-tree__row eidos-json-schema-tree__composition" :style="`--json-schema-depth: ${jsonSchemaSearching ? 0 : source.depth}`">
                            <div class="eidos-json-schema-tree__value">
                                <div class="eidos-json-schema-tree__composition-heading">
                                    <span class="eidos-json-schema-tree__composition-label" x-text="@js(__('phpinnacle-eidos::json-schema.composition'))[source.composition]"></span>
                                    <template x-if="source.composition !== 'allOf'">
                                        <div class="eidos-json-schema-tree__variant-select">
                                            <x-filament::input.wrapper>
                                                <x-filament::input.select
                                                    x-effect="const key = jsonSchemaVariant(source).key; $nextTick(() => $el.value = key)"
                                                    x-on:change.stop="jsonSchemaVariants[source.choiceKey] = $event.target.value"
                                                    x-bind:aria-label="@js(__('phpinnacle-eidos::json-schema.composition'))[source.composition]"
                                                >
                                                    <template x-for="variant in source.variants" :key="variant.key">
                                                        <option :value="variant.key" x-text="jsonSchemaVariantLabel(source, variant)"></option>
                                                    </template>
                                                </x-filament::input.select>
                                            </x-filament::input.wrapper>
                                        </div>
                                    </template>
                                    <template x-for="variant in source.composition === 'allOf' ? [] : [jsonSchemaVariant(source)]" :key="variant.key">
                                        <div x-data="{ get source() { return variant } }" class="eidos-json-schema-tree__variant-meta">
                                            <x-phpinnacle-eidos::json-schema-field :names="false" :description="false" :compact="$compact" />
                                        </div>
                                    </template>
                                </div>
                                <template x-for="variant in source.composition === 'allOf' ? [] : [jsonSchemaVariant(source)]" :key="variant.key">
                                    <span x-show="{{ $descriptions }} && variant.description" class="eidos-json-schema-tree__description" x-text="variant.description"></span>
                                </template>
                            </div>
                        </div>
                    </template>
                    <template x-if="!source.composition">
                        <div
                            class="eidos-json-schema-tree__row"
                            :style="`--json-schema-depth: ${jsonSchemaSearching ? 0 : source.depth}`"
                            :class="{ 'eidos-json-schema-tree__row--selected': jsonSchemaSelected(source), 'eidos-json-schema-tree__row--expandable': jsonSchemaRowExpandable(source) }"
                            :aria-current="jsonSchemaSelected(source) ? 'true' : null"
                            :role="jsonSchemaRowExpandable(source) ? 'button' : null"
                            :tabindex="jsonSchemaRowExpandable(source) ? 0 : null"
                            :aria-label="jsonSchemaRowExpandable(source) ? source.fieldLabel : null"
                            x-effect="$el.ariaExpanded = jsonSchemaRowExpandable(source) ? jsonSchemaIsExpanded(source.key) : null"
                            x-on:click="jsonSchemaRowExpandable(source) && jsonSchemaToggle(source)"
                            x-on:keydown.enter.self.prevent="jsonSchemaRowExpandable(source) && jsonSchemaToggle(source)"
                            x-on:keydown.space.self.prevent="jsonSchemaRowExpandable(source) && jsonSchemaToggle(source)"
                        >
                            @if ($disabled)
                                <span aria-hidden="true" class="eidos-json-schema-tree__branch">
                                    <x-filament::icon icon="heroicon-o-chevron-right" x-show="source.hasChildren && !jsonSchemaSearching" class="eidos-json-schema-tree__arrow" />
                                </span>
                            @else
                                <span x-show="jsonSchemaRowExpandable(source)" aria-hidden="true" class="eidos-json-schema-tree__branch">
                                    <x-filament::icon icon="heroicon-o-chevron-right" class="eidos-json-schema-tree__arrow" />
                                </span>
                                <button type="button" x-show="source.hasChildren && !jsonSchemaSearching && !jsonSchemaRowExpandable(source)" x-on:click.stop="jsonSchemaToggle(source)" :aria-label="@js(__('phpinnacle-eidos::json-schema.expand')) + ' ' + source.fieldLabel" :aria-expanded="jsonSchemaIsExpanded(source.key)" class="eidos-json-schema-tree__branch">
                                    <x-filament::icon icon="heroicon-o-chevron-right" class="eidos-json-schema-tree__arrow" />
                                </button>
                            @endif
                            @if (!$disabled)
                                <template x-if="source.compatible === false || source.value === null">
                            @endif
                            <div
                                @if (!$disabled)
                                    :aria-disabled="jsonSchemaRowExpandable(source) ? null : 'true'"
                                    :class="{ 'eidos-json-schema-tree__value--disabled': !jsonSchemaRowExpandable(source), 'eidos-json-schema-tree__value--muted': !source.hasSelectableDescendants }"
                                    :data-source="source.key"
                                @endif
                                :title="{{ $descriptions }} && source.description ? source.fieldLabel + '\n' + source.description : source.fieldLabel"
                                class="eidos-json-schema-tree__value"
                            >
                                <x-phpinnacle-eidos::json-schema-field :compact="$compact" :properties="$properties" :description="$descriptions" selected="jsonSchemaSelected(source)" />
                            </div>
                            @if (!$disabled)
                                </template>
                                <template x-if="source.compatible !== false && source.value !== null">
                                    <button type="button" x-on:click.stop="jsonSchemaChoose(source)" :title="{{ $descriptions }} && source.description ? source.fieldLabel + '\n' + source.description : source.fieldLabel" :data-source="source.key" :aria-pressed="jsonSchemaSelected(source)" class="eidos-json-schema-tree__value">
                                        <x-phpinnacle-eidos::json-schema-field :compact="$compact" :properties="$properties" :description="$descriptions" selected="jsonSchemaSelected(source)" />
                                    </button>
                                </template>
                            @endif
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </template>
    <div x-show="!jsonSchemaMatches.length" class="eidos-json-schema-tree__empty">{{ $disabled ? __('phpinnacle-eidos::json-schema.empty') : __('phpinnacle-eidos::json-schema.no_matches') }}</div>
</div>

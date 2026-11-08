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
    <template x-ref="schemaNode">
        <div class="eidos-json-schema-tree__node" :class="{ 'eidos-json-schema-tree__node--variant': source.branchOf && !jsonSchemaSearching }" :style="{ '--json-schema-depth': jsonSchemaSearching ? 0 : source.displayDepth }">
            <template x-if="source.composition">
                <fieldset class="eidos-json-schema-tree__composition">
                    <legend class="eidos-json-schema-tree__composition-legend">
                        <code x-text="source.composition"></code>
                        <span x-text="@js(__('phpinnacle-eidos::json-schema.composition'))[source.composition]"></span>
                    </legend>
                    <div class="eidos-json-schema-tree__variants">
                        <template x-for="child in source.children" :key="child.key">
                            <div x-data="{ get source() { return child } }" x-html="$refs.schemaNode.innerHTML"></div>
                        </template>
                    </div>
                </fieldset>
            </template>
            <template x-if="!source.composition">
                <div class="eidos-json-schema-tree__node-body">
                    <div
                        class="eidos-json-schema-tree__row"
                        :class="{ 'eidos-json-schema-tree__row--selected': jsonSchemaSelected(source), 'eidos-json-schema-tree__row--expandable': jsonSchemaRowExpandable(source), 'eidos-json-schema-tree__row--variant': source.branchOf && !jsonSchemaSearching }"
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
                                :aria-disabled="jsonSchemaRowExpandable(source) || source.branchOf ? null : 'true'"
                                :class="{ 'eidos-json-schema-tree__value--disabled': !jsonSchemaRowExpandable(source) && !source.branchOf, 'eidos-json-schema-tree__value--muted': !source.hasSelectableDescendants && !source.branchOf }"
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
                    <template x-if="!jsonSchemaSearching && source.children.length && jsonSchemaIsExpanded(source.key)">
                        <div class="eidos-json-schema-tree__children">
                            <template x-for="child in source.children" :key="child.key">
                                <div x-data="{ get source() { return child } }" x-html="$refs.schemaNode.innerHTML"></div>
                            </template>
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </template>
    <template x-for="group in jsonSchemaMatches" :key="group.key">
        <div class="eidos-json-schema-tree__rows">
            <template x-for="node in (jsonSchemaSearching ? group.sources : group.nodes)" :key="node.key">
                <div x-data="{ get source() { return node } }" x-html="$refs.schemaNode.innerHTML"></div>
            </template>
        </div>
    </template>
    <div x-show="!jsonSchemaMatches.length" class="eidos-json-schema-tree__empty">{{ $disabled ? __('phpinnacle-eidos::json-schema.empty') : __('phpinnacle-eidos::json-schema.no_matches') }}</div>
</div>

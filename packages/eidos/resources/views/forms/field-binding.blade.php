@php
    use Filament\Support\Enums\Size;
    use Filament\Support\Facades\FilamentAsset;

    $sources = $getSources();
    $bindings = $getBindings();
    $childSchemas = $getChildSchemas();
    $sourceStateKeys = $getSourceStateKeys();
    $constants = $getConstants();
    $sourceLabel = $getSourceLabel();
    $targetLabel = $getTargetLabel();
    $hasFields = $sources !== [];
    $searchable = $isSearchable();
    $contained = $isContained();
    $dense = $isDense();
    $compact = $isCompact();
    $columnSpans = $getColumnSpans();
    $disabled = $isDisabled();
    $statePath = $getStatePath();
    $warnings = $getWarnings();
    $unavailableWarnings = array_diff_key($warnings, array_column($sources, null, 'id'));
    $configurationKey = md5(serialize([$sources, $disabled, $searchable, $dense, $compact, $contained, $columnSpans, $sourceLabel, $targetLabel]));
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        {{ $getExtraAttributeBag()->class([
            'field-binding',
            'eidos-mapping-contained' => $contained,
            'fi-dense' => $dense,
            'fi-compact' => $compact,
        ])->style([
            '--eidos-left-column: '.$columnSpans['source'].'fr',
            '--eidos-gap-column: '.$columnSpans['gap'].'fr',
            '--eidos-right-column: '.$columnSpans['target'].'fr',
        ]) }}
        wire:key="{{ $getId() }}-{{ $configurationKey }}"
        x-load
        x-load-src="{{ FilamentAsset::getAlpineComponentSrc('field-binding', 'phpinnacle/eidos') }}"
        x-data="fieldBinding({ state: $wire.$entangle('{{ $statePath }}'), disabled: @js($disabled), sourceKeys: @js(array_column($sources, 'id')), setTarget: (index, value) => $wire.$set(@js($statePath) + '.' + index + '.target', value, false) })"
    >
        @if ($contained || $getHeaderActions() !== [])
            <div class="eidos-mapping-toolbar">
                @if ($headerActions = $getHeaderActions())
                    <div class="eidos-mapping-actions">
                        @foreach ($headerActions as $action)
                            @if ($action->isVisible())
                                {{ $action->defaultSize($dense ? Size::Small : Size::Medium) }}
                            @endif
                        @endforeach
                    </div>
                @endif

                @if ($contained)
                    <div class="eidos-mapping-status">
                        <span class="text-sm text-gray-500 dark:text-gray-400" x-text="@js(__('phpinnacle-eidos::schema-forms.binding.bound_count', ['bound' => '__bound__', 'total' => count($sources)])).replace('__bound__', boundCount())">{{ __('phpinnacle-eidos::schema-forms.binding.bound_count', ['bound' => count($bindings) - count($constants), 'total' => count($sources)]) }}</span>
                        @if ($searchable && $hasFields)
                            <div class="eidos-mapping-search">
                                <x-filament::input.wrapper prefix-icon="heroicon-m-magnifying-glass">
                                    <x-filament::input
                                        type="search"
                                        x-model="search"
                                        placeholder="{{ __('phpinnacle-eidos::schema-forms.search_placeholder') }}"
                                        aria-label="{{ __('phpinnacle-eidos::schema-forms.search_label') }}"
                                    />
                                </x-filament::input.wrapper>
                            </div>
                        @endif
                        <fieldset class="eidos-mapping-filter">
                            <legend class="sr-only">{{ __('phpinnacle-eidos::schema-forms.binding.filter.label') }}</legend>
                            @foreach (['all', 'bound', 'unbound'] as $filter)
                                <label>
                                    <input
                                        type="radio"
                                        name="{{ $getId() }}-visibility-filter"
                                        value="{{ $filter }}"
                                        x-model="visibilityFilter"
                                        class="sr-only"
                                        @checked($filter === 'all')
                                    />
                                    <span>{{ __('phpinnacle-eidos::schema-forms.binding.filter.'.$filter) }}</span>
                                </label>
                            @endforeach
                        </fieldset>
                    </div>
                @endif
            </div>
        @endif

        @foreach ($unavailableWarnings as $id => $warning)
            <div class="field-mapping-warning eidos-mapping-alert" role="alert">
                <x-filament::icon icon="heroicon-m-exclamation-triangle" />
                <span>{{ $id }}: {{ $warning }}</span>
            </div>
        @endforeach

        <div class="field-binding-content">
            @if (! $hasFields)
                <x-filament::empty-state
                    icon="heroicon-o-rectangle-stack"
                    :heading="__('phpinnacle-eidos::schema-forms.binding.empty')"
                    :contained="false"
                />
            @else
                <div class="field-binding-columns">
                    <h3 class="eidos-mapping-label">{{ $sourceLabel }}</h3>
                    <h3 class="eidos-mapping-label field-binding-target">{{ $targetLabel }}</h3>
                </div>

                @foreach ($sources as $source)
                    @php
                        $key = $getBindingKey($source['id']);
                        $binding = $bindings[$key] ?? null;
                        $warning = $warnings[$source['id']] ?? null;
                        $searchText = strip_tags($source['label'].' '.($source['description'] ?? '')).' '.$source['id'].' '.$source['name'].' '.$source['presentation']['type'];
                    @endphp
                    <div
                        class="field-binding-columns"
                        :class="{ 'gap-2': @js($dense) && !isBound(@js($source['id'])), 'gap-3': @js(! $dense) && !isBound(@js($source['id'])) }"
                        wire:key="{{ $getId() }}-{{ $key }}"
                        data-binding-source="{{ $source['id'] }}"
                        x-show="isVisible(@js($source['id']), @js($searchable ? $searchText : ''))"
                    >
                        <div @class([
                            'eidos-mapping-card field-mapping-row field-binding-source',
                            'is-mapped' => $binding !== null,
                            'has-warning' => $warning !== null,
                        ]) :class="{ 'is-mapped': isBound(@js($source['id'])) }">
                            <x-phpinnacle-eidos::mapping-item :compact="$compact" :item="$source" :warning="$warning" />
                            @if (isset($childSchemas['settings_'.$sourceStateKeys[$source['id']]]))
                                <div class="w-full" data-binding-source-settings="{{ $source['id'] }}">
                                    {{ $childSchemas['settings_'.$sourceStateKeys[$source['id']]] }}
                                </div>
                            @endif
                        </div>
                        @php
                            $unbind = ($getAction('unbind'))(['source' => $source['id']]);
                            $unbindLabel = $unbind->getLabel().' '.strip_tags($source['label']);
                        @endphp
                        <button
                            type="button"
                            class="field-binding-connection field-connection-control"
                            aria-label="{{ $unbindLabel }}"
                            title="{{ $unbindLabel }}"
                            x-show="isBound(@js($source['id']))"
                            x-on:click="{{ $unbind->getAlpineClickHandler() }}"
                            @if ($binding === null) x-cloak @endif
                            @disabled($unbind->isDisabled())
                        >
                            <svg class="field-binding-line-horizontal" viewBox="0 0 100 16" preserveAspectRatio="none" aria-hidden="true">
                                <path class="field-mapping-line" d="M 0 8 H 100" vector-effect="non-scaling-stroke" />
                                <path class="field-mapping-hit" d="M 0 8 H 100" vector-effect="non-scaling-stroke" />
                            </svg>
                            <svg class="field-binding-line-vertical" viewBox="0 0 16 100" preserveAspectRatio="none" aria-hidden="true">
                                <path class="field-mapping-line" d="M 8 0 V 100" vector-effect="non-scaling-stroke" />
                                <path class="field-mapping-hit" d="M 8 0 V 100" vector-effect="non-scaling-stroke" />
                            </svg>
                            <span class="field-connection-indicator" aria-hidden="true">
                                <x-filament::icon icon="heroicon-m-x-mark" />
                            </span>
                        </button>
                        @php
                            $bindingDefault = $getBindingDefault($source['id']);
                            $bindingSchemaHtml = $renderBindingSchema($source['id'], $bindingDefault);
                        @endphp
                        <div class="eidos-mapping-card field-binding-target">
                            <fieldset class="min-w-0 flex-1" :inert="!isEditing(@js($source['id']))" :disabled="disabled || !isEditing(@js($source['id']))" @if ($binding === null) inert disabled @endif>
                                <template x-if="isEditing(@js($source['id']))">
                                    <div>{!! $bindingSchemaHtml !!}</div>
                                </template>
                                <template x-if="!isEditing(@js($source['id']))">
                                    <div x-ignore>{!! $bindingSchemaHtml !!}</div>
                                </template>
                            </fieldset>
                            <div class="field-binding-overlay" x-show="!isEditing(@js($source['id']))" @if ($binding !== null) x-cloak @endif>
                                {{ ($getAction('bind'))(['source' => $source['id'], 'default' => $bindingDefault]) }}
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif

            @if ($hasStaticValues())
                {{ $getChildSchema('staticValues') }}
            @endif
        </div>
    </div>
</x-dynamic-component>

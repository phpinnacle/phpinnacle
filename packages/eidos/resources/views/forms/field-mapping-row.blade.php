@php
    $inverse = $field->isInverse();
    $isLeft = $side === ($inverse ? 'target' : 'source');
    $compact = $field->isCompact();
    $disabled = $field->isDisabled();
    $multipleTargets = $side === 'target' ? $field->getMultipleTargets() : [];
    $staticDefaults = $side === 'target' ? $field->getStaticDefaults() : [];
    $stateKeys = $side === 'target' ? $field->getStateKeys() : [];
    $staticSchemas = $field->getChildSchemas();
    $sourceStateKeys = $field->getSourceStateKeys();
    $statePath = $field->getStatePath();
    $warnings = $side === 'source' ? $field->getSourceWarnings() : $field->getTargetWarnings();
    $plainLabel = strip_tags($item['label']);
    $warning = $warnings[$item['id']] ?? null;
    $typeLabel = $presentation['type'];
    $searchText = $plainLabel.' '.strip_tags($item['description'] ?? '').' '.$item['id'].' '.$item['name'].' '.$typeLabel.' '.implode(' ', array_column($item['badges'], 'label'));
    $isMultiple = $side === 'target' && in_array($item['id'], $multipleTargets, true);
    $staticSchema = $side === 'target' && array_key_exists($item['id'], $staticDefaults)
        ? ($staticSchemas[$stateKeys[$item['id']]] ?? $field->getStaticFormSchema($item['id']))
        : null;
    $hasSourceFooter = $side === 'source' && isset($staticSchemas['settings_'.$sourceStateKeys[$item['id']]]);
@endphp
<div
    @class([
        'eidos-mapping-card field-mapping-row',
        'has-warning' => $warning !== null,
    ])
    data-mapping-row
    x-on:click="selectFromCard($event, @js($side), @js($item['id']))"
    wire:key="{{ $field->getId() }}-{{ $side }}-{{ $item['id'] }}"
    @if ($side === 'target')
        x-show="isVisible(@js($side), @js($item['id']), @js($searchText))"
    @endif
    :class="rowClasses(@js($side), @js($item['id']), @js($item['required']))"
>
    <div class="field-mapping-row-heading">
        <div
            @class(['field-mapping-row-content', 'has-footer' => $hasSourceFooter])
        >
            <button
                type="button"
                class="field-mapping-select"
                x-bind="fieldBindings(@js($side), @js($item['id']))"
                aria-label="{{ 'Select '.$plainLabel.' as '.$side }}"
                @disabled($disabled)
            ></button>
            @if ($side === 'source')
                <div class="relative min-w-0 flex-1">
                    {!! $staticSchemas['sourceCards']->getComponent('heading_'.$sourceStateKeys[$item['id']])->toSchemaHtml() !!}
                </div>
            @else
                <x-phpinnacle-eidos::mapping-item :compact="$compact" class="relative" :item="$item" :presentation="$presentation" :multiple="$isMultiple" :warning="$warning">
                    @if ($isMultiple)
                        <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400" x-show="isMapped('target', @js($item['id'])) && !isConstant(@js($item['id']))" x-cloak>
                            <span x-show="targetMode(@js($item['id'])) === 'item'">{{ __('phpinnacle-eidos::schema-forms.mapping.array_items') }}</span>
                            <span x-show="targetMode(@js($item['id'])) === 'property'">{{ __('phpinnacle-eidos::schema-forms.mapping.object_properties') }}</span>
                        </span>
                    @endif
                    @if ($side === 'target')
                        <span class="mt-1 block text-xs text-danger-600 dark:text-danger-400" x-show="hasTypeConflict(@js($item['id']))" x-cloak>{{ __('phpinnacle-eidos::schema-forms.mapping.incompatible') }}</span>
                    @endif
                </x-phpinnacle-eidos::mapping-item>
            @endif
        </div>
        <button
            type="button"
            class="field-mapping-port {{ $isLeft ? 'is-left' : 'is-right' }}"
            data-mapping-port
            data-side="{{ $side }}"
            data-id="{{ $item['id'] }}"
            data-label="{{ $plainLabel }}"
            x-bind="fieldBindings(@js($side), @js($item['id']))"
            aria-label="{{ 'Connect '.$plainLabel.' ('.$side.')' }}"
            @disabled($disabled)
        >
            <span class="field-mapping-anchor" data-mapping-anchor aria-hidden="true">
                <x-filament::icon icon="heroicon-m-check" class="field-mapping-available-icon" />
                <x-filament::icon icon="heroicon-m-x-mark" class="field-mapping-unavailable-icon" />
            </span>
        </button>
    </div>
    @if ($hasSourceFooter)
        <div class="field-mapping-row-footer field-mapping-source-footer">
            <div class="min-w-0 flex-1" data-mapping-source-settings="{{ $item['id'] }}">
                {{ $staticSchemas['settings_'.$sourceStateKeys[$item['id']]] ?? null }}
            </div>
        </div>
    @endif
    @if ($staticSchema !== null)
        {{-- Mount against Livewire's client state so nested field entanglements see their value. --}}
        <template x-if="isConstant(@js($item['id']))">
            <div class="field-mapping-row-footer" data-mapping-static-form="{{ $item['id'] }}">
                {{ $staticSchema }}
            </div>
        </template>
    @endif
</div>

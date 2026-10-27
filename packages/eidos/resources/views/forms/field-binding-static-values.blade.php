@php
    use Illuminate\Support\Str;
    use PHPinnacle\Eidos\Forms\FieldBinding;

    /** @var FieldBinding $field */
    $field = $getContainer()->getParentComponent();
@endphp
<div class="field-binding-static-values" data-binding-constants>
    @foreach ($field->getConstants() as $key => $constant)
        @php
            $target = $field->getConstantTarget($key);
            $property = $field->getSimpleField() === null ? null : (is_string($target) ? $target : json_encode($target, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
            $title = $property === null ? __('phpinnacle-eidos::schema-forms.binding.static_value') : Str::headline($property);
            $remove = ($field->getAction('removeConstant'))(['constant' => $key]);
            $removeLabel = $remove->getLabel().' '.($property ?? $title);
        @endphp
        <div class="field-binding-columns gap-y-2" wire:key="{{ $field->getId() }}-{{ $key }}" data-binding-constant="{{ $key }}" x-show="matchesFilter(true)">
            <div class="eidos-mapping-card field-mapping-row is-mapped space-y-2">
                <div class="eidos-mapping-heading">
                    <span class="eidos-mapping-names">
                        <span class="eidos-mapping-name has-title">{{ $title }}</span>
                        @if ($property !== null)
                            <span class="eidos-mapping-property">{{ $property }}</span>
                        @endif
                    </span>
                    <span class="eidos-mapping-types">
                        <span class="eidos-mapping-type">{{ $field->getConstantInputSchema($key)->singleType() }}</span>
                    </span>
                </div>
                {{ $constant->getComponent('input') }}
            </div>
            <button
                type="button"
                class="field-binding-connection field-connection-control"
                aria-label="{{ $removeLabel }}"
                title="{{ $removeLabel }}"
                wire:click="{{ $remove->getLivewireClickHandler() }}"
                wire:loading.attr="disabled"
                wire:target="{{ $remove->getLivewireClickHandler() }}"
                @disabled($remove->isDisabled())
            >
                <svg class="field-binding-line-horizontal" viewBox="0 0 100 16" preserveAspectRatio="none" aria-hidden="true"><path class="field-mapping-line" d="M 0 8 H 100" vector-effect="non-scaling-stroke" /></svg>
                <svg class="field-binding-line-vertical" viewBox="0 0 16 100" preserveAspectRatio="none" aria-hidden="true"><path class="field-mapping-line" d="M 8 0 V 100" vector-effect="non-scaling-stroke" /></svg>
                <span class="field-connection-indicator" aria-hidden="true">
                    <x-filament::icon :icon="$remove->getIcon()" />
                </span>
            </button>
            <div class="eidos-mapping-card field-binding-target">
                <div class="min-w-0 flex-1">{{ $constant->getComponent('output') }}</div>
            </div>
        </div>
    @endforeach
    <div>{{ $field->getAction('addConstant') }}</div>
</div>

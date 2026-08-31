@php
    use Illuminate\View\ComponentAttributeBag;
    use function Filament\Support\prepare_inherited_attributes;

    $id = $getId();
    $fieldWrapperView = $getFieldWrapperView();
    $extraAlpineAttributes = $getExtraAlpineAttributes();
    $currencyAttributeBag = new ComponentAttributeBag();
    $hasInlineLabel = $hasInlineLabel();

    $isConcealed = $isConcealed();
    $isDisabled = $isDisabled();
    $isPrefixInline = $isPrefixInline();
    $isSuffixInline = $isSuffixInline();

    $statePath = $getStatePath();
    $currencies = $getCurrencies();
    $currencyDisabled = $isDisabled || count($currencies) <= 1;

    $prefixLabel = $getPrefixLabel();
    $prefixIcon = $getPrefixIcon();
    $prefixIconColor = $getPrefixIconColor();
    $prefixActions = $getPrefixActions();

    $suffixLabel = count($currencies) === 1 ? current($currencies) : null;
    $suffixIcon = $getSuffixIcon();
    $suffixIconColor = $getSuffixIconColor();
    $suffixActions = $getSuffixActions();

    $xData = '{}';
    $type = 'text';
@endphp

<x-dynamic-component
    :component="$fieldWrapperView"
    :field="$field"
    :has-inline-label="$hasInlineLabel"
>
    <x-filament::input.wrapper
        :disabled="$isDisabled"
        :inline-prefix="$isPrefixInline"
        :inline-suffix="$isSuffixInline"
        :prefix="$prefixLabel"
        :prefix-actions="$prefixActions"
        :prefix-icon="$prefixIcon"
        :prefix-icon-color="$prefixIconColor"
        :suffix="$suffixLabel"
        :suffix-actions="$suffixActions"
        :suffix-icon="$suffixIcon"
        :suffix-icon-color="$suffixIconColor"
        :valid="!$errors->has($statePath . '.amount')"
        :x-data="$xData"
        :attributes="
            prepare_inherited_attributes($getExtraAttributeBag())
                ->class(['fi-fo-text-input relative overflow-hidden'])
        "
    >
        <x-filament::input
            :attributes="
                prepare_inherited_attributes($getExtraInputAttributeBag())
                    ->merge($extraAlpineAttributes, escape: false)
                    ->merge([
                        'id' => $id,
                        'autofocus' => $isAutofocused(),
                        'disabled' => $isDisabled,
                        'max' => (! $isConcealed) ? $getMaxValue() : null,
                        'min' => (! $isConcealed) ? $getMinValue() : null,
                        'placeholder' => $getPlaceholder(),
                        'readonly' => $isReadOnly(),
                        'required' => $isRequired() && (! $isConcealed),
                        'step' => $getStep(),
                        'type' => $type,
                        $applyStateBindingModifiers('wire:model') => $statePath . '.amount',
                    ], escape: false)
            "
        />
        @if(count($currencies) > 1)
        <div class="absolute inset-y-0 right-0 flex items-center">
            <x-filament::input.select
                    :id="$getId() . '-currency'"
                    :required="true"
                    :attributes="
                $currencyAttributeBag
                    ->merge([
                        'wire:model' => $statePath . '.currency',
                    ], escape: false)
            "
            >
                @foreach ($currencies as $currency)
                <option value="{{ $currency }}">{{ $currency }}</option>
                @endforeach
            </x-filament::input.select>
        </div>
        @else
        <input type="hidden" wire:model="{{ sprintf('%s.currency', $statePath) }}" />
        @endif
    </x-filament::input.wrapper>
</x-dynamic-component>

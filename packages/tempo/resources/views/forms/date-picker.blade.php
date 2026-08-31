@php
    $id = $getId();
    $fieldWrapperView = $getFieldWrapperView();
    $extraAlpineAttributes = $getExtraAlpineAttributes();
    $extraAttributeBag = $getExtraAttributeBag();
    $isConcealed = $isConcealed();
    $isDisabled = $isDisabled();
    $isPrefixInline = $isPrefixInline();
    $isSuffixInline = $isSuffixInline();
    $prefixActions = $getPrefixActions();
    $prefixIcon = $getPrefixIcon();
    $prefixIconColor = $getPrefixIconColor();
    $prefixLabel = $getPrefixLabel();
    $suffixActions = $getSuffixActions();
    $suffixIcon = $getSuffixIcon();
    $suffixIconColor = $getSuffixIconColor();
    $suffixLabel = $getSuffixLabel();
    $statePath = $getStatePath();
    $placeholder = $getPlaceholder();

    $inputAttributes = $getExtraInputAttributeBag()
        ->merge($extraAlpineAttributes, escape: false)
        ->merge([
            'id' => $id,
            'disabled' => $isDisabled,
            'inlinePrefix' => $isPrefixInline && (count($prefixActions) || $prefixIcon || filled($prefixLabel)),
            'inlineSuffix' => $isSuffixInline && (count($suffixActions) || $suffixIcon || filled($suffixLabel)),
            'placeholder' => filled($placeholder) ? e($placeholder) : null,
            'readonly' => $isReadOnly(),
            'required' => $isRequired() && (! $isConcealed),
            'type' => 'text',
            'autocomplete' => 'off',
            'x-ref' => 'input',
        ], escape: false);
@endphp

<x-dynamic-component
    :component="$fieldWrapperView"
    :field="$field"
    :inline-label-vertical-alignment="\Filament\Support\Enums\VerticalAlignment::Center"
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
        :valid="! $errors->has($statePath)"
        x-on:focus-input.stop="$el.querySelector('input')?.focus()"
        :attributes="
            \Filament\Support\prepare_inherited_attributes($extraAttributeBag)
                ->class(['fi-fo-text-input'])
        "
    >
        <div
            wire:ignore
            x-load
            x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('phpicker', 'phpinnacle/tempo') }}"
            x-data="phpPicker({
                state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$getStatePath()}')") }},
                mode: @js($getMode()),
                dateFormat: @js($getMomentDateFormat()),
                timeFormat: @js($getMomentTimeFormat()),
                fullFormat: @js($getMomentFullFormat()),
                maskFormat: @js($getMomentMaskFormat()),
                minDate: @js($getMinDate()),
                maxDate: @js($getMaxDate()),
                autoclose: @js($getAutoclose()),
                separator: @js($getSeparator()),
            })"
        >
            <input
                {{
                    $inputAttributes->class([
                        'fi-input',
                        'fi-input-has-inline-prefix' => $isPrefixInline && (count($prefixActions) || $prefixIcon || filled($prefixLabel)),
                        'fi-input-has-inline-suffix' => $isSuffixInline && (count($suffixActions) || $suffixIcon || filled($suffixLabel)),
                    ])
                }}
            />
        </div>
    </x-filament::input.wrapper>
</x-dynamic-component>

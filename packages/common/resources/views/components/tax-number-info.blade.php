@php
    use Filament\Support\Facades\FilamentAsset;
    use Illuminate\Support\Js;

    $field = $action->getSchemaComponent();
    $disabled = $action->isDisabled();
    $signature = json_encode([$field->getCountry(), $field->getState(), $disabled], JSON_THROW_ON_ERROR);
@endphp

<div
    wire:key="{{ $field->getKey() }}-tax-number-info-{{ md5($signature) }}"
    x-load
    x-load-src="{{ FilamentAsset::getAlpineComponentSrc('tax-number-popup', 'phpinnacle/common') }}"
    x-data="taxNumberPopup({
        lookup: () => $wire.callSchemaComponentMethod(@js($field->getKey()), 'lookupForJs'),
        unavailable: @js(__('phpinnacle-common::tax-number.unavailable')),
    })"
>
    <x-filament::dropdown
        :menu="false"
        placement="bottom-end"
        width="xs"
        :offset="16"
        shift
        teleport
        :x-on:mousedown.capture="'if (loading || ' . Js::from($disabled)->toHtml() . ') $event.stopPropagation()'"
        :x-on:keydown.capture="'if (loading || ' . Js::from($disabled)->toHtml() . ') $event.stopPropagation()'"
    >
        <x-slot name="trigger">
            <span class="common-tax-number-trigger" x-bind:class="{ 'is-loading': loading }">
                <x-filament::icon-button
                    :icon="$action->getIcon()"
                    :label="$action->getLabel()"
                    :title="$action->getTooltip() ?? $action->getLabel()"
                    :disabled="$disabled"
                    :wire:loading.attr="false"
                    :wire:loading.class="false"
                    :x-bind:disabled="'loading || ' . Js::from($disabled)->toHtml()"
                    x-bind:aria-busy="loading"
                    size="sm"
                />
                <x-filament::loading-indicator x-cloak x-show="loading" class="common-tax-number-trigger__loading" />
            </span>
        </x-slot>

        <div
            class="common-tax-number-popup"
            role="region"
            aria-label="{{ $action->getLabel() }}"
            aria-live="polite"
            x-bind:aria-busy="loading"
            x-init="$watch('isOpen', open => { if (open) load() })"
        >
            <p x-show="loading" role="status" x-text="@js(__('phpinnacle-common::tax-number.loading'))"></p>
            <p x-cloak x-show="message" x-text="message"></p>
            <template x-if="lookup">
                <div class="common-tax-number-popup__details">
                    <p class="common-tax-number-popup__name" x-text="lookup.name"></p>
                    <p x-show="lookup.status !== null" class="common-tax-number-popup__status" x-text="lookup.status"></p>
                    <p x-show="lookup.address !== null" x-text="lookup.address"></p>
                </div>
            </template>
            <p class="common-tax-number-popup__caption">{{ $action->getLabel() }} · {{ $field->getCountry() }}</p>
        </div>
    </x-filament::dropdown>
</div>

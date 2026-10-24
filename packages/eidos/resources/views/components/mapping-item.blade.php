@props(['item', 'presentation' => null, 'multiple' => false, 'warning' => null, 'compact' => false])

@php
    $presentation ??= $item['presentation'];
    $hasTitle = strip_tags($item['label']) !== $item['name'];
    $color = $item['color'];
    $literalColor = $color !== null && (str_starts_with($color, '#') || str_starts_with($color, 'rgb'));
    $hasItems = $presentation['items'] !== [];
@endphp

<span
    {{ $attributes->class(['eidos-mapping-item', 'has-icon' => $item['icon'] !== null]) }}
    @if ($color !== null)
        style="--mapping-field-color: {{ $literalColor ? $color : 'var(--'.$color.'-600)' }}; --mapping-field-color-dark: {{ $literalColor ? $color : 'var(--'.$color.'-400)' }}"
    @endif
    @if ($hasItems)
        x-data="{ itemsOpen: false }"
        x-id="['mapping-items']"
    @endif
    data-mapping-item
>
    <span class="eidos-mapping-heading" data-mapping-heading>
        @if ($item['icon'] !== null)
            <span class="eidos-mapping-icon" data-mapping-icon aria-hidden="true">
                <x-filament::icon :icon="$item['icon']" />
            </span>
        @endif
        <span class="eidos-mapping-names">
            <span
                @class(['eidos-mapping-name', 'has-title' => $hasTitle])
                @if ($compact && $item['description'] !== null)
                    x-tooltip="{ content: @js($item['description']), theme: $store.theme }"
                    tabindex="0"
                @endif
            >{{ $item['label'] }}</span>
            @if ($item['required'])
                <span class="eidos-mapping-required"><span aria-hidden="true">*</span><span class="fi-sr-only">{{ __('phpinnacle-eidos::schema-forms.mapping.required') }}</span></span>
            @endif
            @if ($hasTitle)
                <span class="eidos-mapping-property">{{ $item['name'] }}</span>
            @endif
        </span>
        <span class="eidos-mapping-types" data-mapping-badges>
            @foreach ($item['badges'] as $badge)
                <x-filament::badge :color="$badge['color']" size="sm">{{ $badge['label'] }}</x-filament::badge>
            @endforeach
            <x-phpinnacle-eidos::mapping-constraints :constraints="$presentation['constraints']" />
            @if ($presentation['nullable'])
                <span class="eidos-mapping-type is-null">null</span>
            @endif
            @if ($multiple)
                <span class="fi-sr-only">{{ __('phpinnacle-eidos::schema-forms.mapping.multiple') }}</span>
            @endif
            <span
                @class(['eidos-mapping-type', 'is-null' => $presentation['type'] === 'null'])
                @if ($presentation['enum'] !== [])
                    title="{{ __('phpinnacle-eidos::schema-forms.mapping.allowed_values') }}: {{ implode(', ', $presentation['enum']) }}"
                @endif
            >{{ $presentation['type'] }}</span>
            @if ($hasItems)
                <button
                    type="button"
                    class="eidos-mapping-details-toggle"
                    data-mapping-details-toggle
                    x-on:click.stop="itemsOpen = !itemsOpen"
                    :aria-expanded="itemsOpen"
                    :aria-controls="$id('mapping-items')"
                    aria-label="{{ __('phpinnacle-eidos::schema-forms.mapping.show_item_types', ['field' => strip_tags($item['label'])]) }}"
                    :aria-label="itemsOpen ? @js(__('phpinnacle-eidos::schema-forms.mapping.hide_item_types', ['field' => strip_tags($item['label'])])) : @js(__('phpinnacle-eidos::schema-forms.mapping.show_item_types', ['field' => strip_tags($item['label'])]))"
                >
                    <x-filament::icon icon="heroicon-m-chevron-down" aria-hidden="true" />
                </button>
            @endif
        </span>
    </span>
    @if (! $compact && $item['description'] !== null)
        <span class="eidos-mapping-description" data-mapping-description>{{ $item['description'] }}</span>
    @endif
    @if ($hasItems)
        <span class="eidos-mapping-details" role="group" aria-label="{{ strip_tags($item['label']) }}" data-mapping-details x-show="itemsOpen" x-cloak :id="$id('mapping-items')">
            @foreach ($presentation['items'] as $level)
                <span class="eidos-mapping-details-row">
                    <span class="eidos-mapping-details-label">
                        <span>{{ implode(' → ', array_fill(0, count($level['scope']), __('phpinnacle-eidos::schema-forms.mapping.constraints.items'))) }}</span>
                        @if ($level['nullable'])
                            <span class="eidos-mapping-type is-null">null</span>
                        @endif
                        <span class="eidos-mapping-type">{{ $level['type'] }}</span>
                    </span>
                    <span class="eidos-mapping-details-values">
                        <x-phpinnacle-eidos::mapping-constraints :constraints="$level['constraints']" :parent-scope="$level['scope']" />
                        @foreach ($level['enum'] as $value)
                            <span class="eidos-mapping-type">{{ $value }}</span>
                        @endforeach
                    </span>
                </span>
            @endforeach
        </span>
    @endif
    @if ($warning !== null)
        <span class="field-mapping-warning" role="alert">
            <x-filament::icon icon="heroicon-m-exclamation-triangle" />
            <span>{{ $warning }}</span>
        </span>
    @endif
    {{ $slot }}
</span>

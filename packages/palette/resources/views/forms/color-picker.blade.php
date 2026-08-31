@php
    use Filament\Support\Facades\FilamentAsset;
    use PHPinnacle\Palette\ColorFormat;
    use PHPinnacle\Palette\ColorSource;
    use PHPinnacle\Palette\PaletteServiceProvider;

    $statePath = $getStatePath();
    $isDisabled = $isDisabled();
    $colorFormat = $getColorFormat();
    $hasTailwind = $hasSource(ColorSource::Tailwind);
    $hasFilament = $hasSource(ColorSource::Filament);
    $hasCustom = $hasSource(ColorSource::Custom);
    $tailwindColors = $hasTailwind ? $getTailwindColors() : [];
    $themeColors = $hasFilament ? $getThemeColors() : [];
    $isWidePopover = $isWide();
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        @class([
            'phpinnacle-palette-color-picker',
            'is-wide' => $isWidePopover,
        ])
        x-load
        x-load-src="{{ FilamentAsset::getAlpineComponentSrc('color-picker', PaletteServiceProvider::PACKAGE) }}"
        x-data="paletteColorPicker({
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            format: @js($colorFormat->value),
            themeColors: @js($themeColors),
            wide: @js($isWidePopover),
        })"
    >
        <x-filament::dropdown
            placement="bottom-start"
            :flip="true"
            :shift="true"
            width="none"
        >
            <x-slot name="trigger">
                <x-filament::input.wrapper
                    class="phpinnacle-palette-color-picker__input"
                    :disabled="$isDisabled"
                    :valid="! $errors->has($statePath)"
                >
                    <button
                        type="button"
                        class="phpinnacle-palette-color-picker__trigger"
                        @disabled($isDisabled)
                    >
                        <span
                            class="phpinnacle-palette-color-picker__preview"
                            x-bind:class="{ 'is-empty': ! state }"
                            x-bind:style="{ backgroundColor: previewColor() }"
                        ></span>

                        <span
                            class="phpinnacle-palette-color-picker__value"
                            x-bind:class="{ 'is-placeholder': ! state }"
                            x-text="state || @js($getPlaceholder())"
                        ></span>

                        <x-filament::icon
                            icon="heroicon-m-chevron-up-down"
                            class="phpinnacle-palette-color-picker__chevron"
                        />
                    </button>
                </x-filament::input.wrapper>
            </x-slot>

            <div class="phpinnacle-palette-color-picker__panel">
                <button
                    type="button"
                    class="phpinnacle-palette-color-picker__reset"
                    x-on:click="state = null"
                >
                    <x-filament::icon icon="heroicon-m-no-symbol" />
                    <span>{{ __('phpinnacle-palette::color-picker.reset') }}</span>
                </button>

                @if ($hasTailwind)
                    <section class="phpinnacle-palette-color-picker__section">
                        <h3>{{ __('phpinnacle-palette::color-picker.tailwind') }}</h3>

                        <div class="phpinnacle-palette-color-picker__palettes">
                            @foreach ($tailwindColors as $name => $colors)
                                <div
                                    class="phpinnacle-palette-color-picker__palette"
                                    aria-label="{{ str($name)->headline() }}"
                                >
                                    @foreach ($colors as $shade => $color)
                                        <button
                                            type="button"
                                            class="phpinnacle-palette-color-picker__swatch"
                                            style="background-color: {{ $color }}"
                                            title="{{ str($name)->headline() }} {{ $shade }} · {{ $color }}"
                                            aria-label="{{ str($name)->headline() }} {{ $shade }}, {{ $color }}"
                                            x-bind:class="{ 'is-selected': isSelected(@js($color)) }"
                                            x-on:click="selectColor(@js($color))"
                                        >
                                            <span
                                                aria-hidden="true"
                                                x-show="isSelected(@js($color))"
                                            >✓</span>
                                        </button>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                @if ($hasFilament)
                    <section class="phpinnacle-palette-color-picker__section">
                        <h3>{{ __('phpinnacle-palette::color-picker.theme') }}</h3>

                        <div class="phpinnacle-palette-color-picker__theme">
                            @foreach ($themeColors as $name => $color)
                                @php($value = $getThemeValue($name, $color))

                                <button
                                    type="button"
                                    class="phpinnacle-palette-color-picker__swatch"
                                    style="background-color: {{ $color }}"
                                    title="{{ str($name)->headline() }} · {{ $color }}"
                                    aria-label="{{ str($name)->headline() }}, {{ $color }}"
                                    x-bind:class="{ 'is-selected': isSelected(@js($value)) }"
                                    x-on:click="selectColor(@js($value))"
                                >
                                    <span
                                        aria-hidden="true"
                                        x-show="isSelected(@js($value))"
                                    >✓</span>
                                </button>
                            @endforeach
                        </div>
                    </section>
                @endif

                @if ($hasCustom)
                    <section class="phpinnacle-palette-color-picker__section">
                        <h3>{{ __('phpinnacle-palette::color-picker.custom') }}</h3>

                        <div class="phpinnacle-palette-color-picker__custom">
                            <label
                                class="phpinnacle-palette-color-picker__custom-swatch"
                                title="{{ __('phpinnacle-palette::color-picker.choose_custom') }}"
                            >
                                <input
                                    type="color"
                                    x-model="customColor"
                                    x-on:input="selectCustomColor()"
                                />
                                <x-filament::icon icon="heroicon-m-plus" />
                                <span class="fi-sr-only">
                                    {{ __('phpinnacle-palette::color-picker.choose_custom') }}
                                </span>
                            </label>

                            <input
                                type="text"
                                class="phpinnacle-palette-color-picker__custom-input"
                                x-model="customColor"
                                x-on:change="selectCustomColor()"
                                placeholder="#000000"
                                aria-label="{{ __('phpinnacle-palette::color-picker.custom_value') }}"
                            />
                        </div>

                        @if ($colorFormat === ColorFormat::Rgba)
                            <label class="phpinnacle-palette-color-picker__opacity">
                                <span>{{ __('phpinnacle-palette::color-picker.opacity') }}</span>
                                <input
                                    type="range"
                                    min="0"
                                    max="1"
                                    step="0.01"
                                    x-model.number="alpha"
                                    x-on:input="selectCustomColor()"
                                />
                                <output x-text="`${Math.round(alpha * 100)}%`"></output>
                            </label>
                        @endif
                    </section>
                @endif
            </div>
        </x-filament::dropdown>
    </div>
</x-dynamic-component>

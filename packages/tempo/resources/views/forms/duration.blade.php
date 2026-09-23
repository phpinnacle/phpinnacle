@php
    $statePath = $getStatePath();
    $disabled = $isDisabled();
    $labels = __('phpinnacle-tempo::duration');
    $storageFormat = $getStorageFormat();
    $units = $getUnits();
    $presets = $getPresets();
    $quickValues = $getQuickValues();
    $configurationKey = md5(serialize([$storageFormat, $units, $presets, $quickValues]));
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        class="tempo-duration"
        wire:key="{{ $getId() }}-{{ $configurationKey }}"
        wire:ignore
        x-load
        x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('tempo-duration', 'phpinnacle/tempo') }}"
        x-data="tempoDuration({
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            labels: @js($labels),
            storageFormat: @js($storageFormat),
            enabledUnits: @js($units),
            presets: @js($presets),
            quickValues: @js($quickValues),
        })"
    >
        <div class="tempo-duration__toolbar">
            <div class="tempo-duration__modes" role="group" aria-label="{{ $labels['mode'] }}">
                <button
                    type="button"
                    class="tempo-duration__mode"
                    :aria-pressed="mode === 'visual'"
                    x-on:click="showVisual()"
                    :disabled="@js($disabled) || !canShowVisual()"
                >{{ $labels['visual'] }}</button>
                <button
                    type="button"
                    class="tempo-duration__mode"
                    :aria-pressed="mode === 'text'"
                    x-on:click="showText()"
                    @disabled($disabled)
                >{{ $labels['text'] }}</button>
            </div>
            @if ($presets !== [])
                <div
                    class="tempo-duration__preset"
                    x-show="presetKeys().length > 0"
                    x-on:click.outside="presetOpen = false"
                    x-on:keydown.escape.window="presetOpen = false"
                >
                    <button
                        type="button"
                        class="tempo-duration__mode tempo-duration__preset-trigger"
                        x-ref="presetTrigger"
                        aria-label="{{ $labels['preset'] }}"
                        aria-haspopup="true"
                        aria-controls="{{ $getId() }}-presets"
                        :aria-expanded="presetOpen"
                        :title="presetLabel()"
                        x-on:click="presetOpen = !presetOpen"
                        @disabled($disabled)
                    >
                        <span x-text="presetLabel()"></span>
                    </button>
                    <div
                        id="{{ $getId() }}-presets"
                        class="tempo-duration__preset-panel"
                        x-show="presetOpen"
                        role="group"
                        aria-label="{{ $labels['preset'] }}"
                        x-cloak
                    >
                        <template x-for="key in presetKeys()" :key="key">
                            <button
                                type="button"
                                class="tempo-duration__preset-option"
                                :aria-pressed="preset === key"
                                x-text="presetLabel(key)"
                                x-on:click="choosePreset(key); $refs.presetTrigger.focus()"
                                @disabled($disabled)
                            ></button>
                        </template>
                    </div>
                </div>
            @endif
        </div>

        <div
            class="tempo-duration__visual"
            x-ref="visual"
            x-show="mode === 'visual'"
            x-on:click.outside="closePart()"
            x-on:keydown.escape.window="closePart(true)"
            x-on:resize.window="activePart && positionPopup()"
            x-cloak
        >
            <div class="tempo-duration__flow">
                <template x-for="part in visibleUnits()" :key="part.name">
                    <button
                        type="button"
                        class="tempo-duration__part"
                        :class="{ 'tempo-duration__part--active': activePart === part.name }"
                        :aria-expanded="activePart === part.name"
                        :aria-controls="'{{ $getId() }}-editor-' + part.name"
                        x-on:click="togglePart(part, $event.currentTarget)"
                        :disabled="@js($disabled)"
                    >
                        <span class="tempo-duration__part-label" x-text="labels.units[part.name]"></span>
                        <span class="tempo-duration__part-value" x-text="parts[part.name]"></span>
                    </button>
                </template>
            </div>

            <div
                class="tempo-duration__fields"
                x-show="activePart !== null"
                :class="{ 'tempo-duration__fields--above': popupAbove }"
                :style="{ left: popupLeft + 'px' }"
                x-cloak
            >
                <template x-for="part in visibleUnits()" :key="part.name">
                    <div
                        class="tempo-duration__field"
                        :id="'{{ $getId() }}-editor-' + part.name"
                        x-show="activePart === part.name"
                        x-cloak
                    >
                        <div class="tempo-duration__field-header">
                            <span class="tempo-duration__label" x-text="labels.units[part.name]"></span>
                            <button
                                type="button"
                                class="tempo-duration__close"
                                x-on:click="closePart(true)"
                                :aria-label="labels.close"
                            >×</button>
                        </div>
                        <input
                            class="tempo-duration__input"
                            type="text"
                            inputmode="numeric"
                            autocomplete="off"
                            :aria-label="labels.units[part.name]"
                            :value="parts[part.name]"
                            x-on:input="updatePart(part, $event.target.value)"
                            @disabled($disabled)
                        />
                        <div class="tempo-duration__quick-values" role="group" :aria-label="labels.units[part.name]">
                            <template x-for="value in quickValues(part)" :key="value">
                                <button
                                    type="button"
                                    class="tempo-duration__quick-value"
                                    :aria-pressed="parts[part.name] === String(value)"
                                    x-text="value"
                                    x-on:click="updatePart(part, String(value))"
                                    @disabled($disabled)
                                ></button>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div class="tempo-duration__text" x-ref="text" x-show="mode === 'text'" x-cloak>
            <input
                id="{{ $getId() }}-text"
                class="tempo-duration__text-input"
                type="text"
                inputmode="numeric"
                autocomplete="off"
                aria-label="{{ $labels['text'] }}"
                :value="textValue()"
                x-on:beforeinput="handleTextBeforeInput($event)"
                x-on:input="restoreTextValue($event)"
                x-on:keydown="handleTextKeydown($event)"
                x-on:paste="handleTextPaste($event)"
                x-on:blur="normalizeTextOnBlur($event)"
                :aria-invalid="raw !== '' && !canShowVisual()"
                @disabled($disabled)
            />
            <button
                type="button"
                class="tempo-duration__text-clear"
                x-show="raw !== ''"
                x-on:click="clearText()"
                aria-label="{{ $labels['clear'] }}"
                @disabled($disabled)
            >×</button>
        </div>
        <p class="tempo-duration__hint tempo-duration__hint--error" x-show="raw !== '' && !canShowVisual()" x-cloak>
            <code x-text="raw"></code> — <span x-text="labels.invalid.replace('{value}', textPlaceholder())"></span>
        </p>
    </div>
</x-dynamic-component>

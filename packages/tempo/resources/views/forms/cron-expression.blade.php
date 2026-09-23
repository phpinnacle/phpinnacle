@php
    $statePath = $getStatePath();
    $disabled = $isDisabled();
    $labels = __('phpinnacle-tempo::cron');
    $gridColumns = $getGridColumns();
    $showDescription = $showsDescription();
    $showDayOfWeek = $showsDayOfWeek();
    $defaultMode = $getDefaultMode();
    $presets = $getPresets();
    $configurationKey = md5(serialize([$gridColumns, $showDescription, $showDayOfWeek, $defaultMode, $presets]));
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        class="tempo-cron"
        wire:key="{{ $getId() }}-{{ $configurationKey }}"
        wire:ignore
        x-load
        x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('tempo-cron', 'phpinnacle/tempo') }}"
        x-data="tempoCron({
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            labels: @js($labels),
            gridColumns: @js($gridColumns),
            showDayOfWeek: @js($showDayOfWeek),
            defaultMode: @js($defaultMode),
            presets: @js($presets),
        })"
    >
        <div class="tempo-cron__toolbar">
            <div class="tempo-cron__modes" role="group" aria-label="{{ $labels['mode'] }}">
                <button type="button" class="tempo-cron__mode" :aria-pressed="mode === 'visual'" x-on:click="showVisual()" :disabled="@js($disabled) || !canShowVisual()">{{ $labels['visual'] }}</button>
                <button type="button" class="tempo-cron__mode" :aria-pressed="mode === 'expression'" x-on:click="mode = 'expression'" @disabled($disabled)>{{ $labels['expression'] }}</button>
            </div>
            @if ($presets !== [])
                <div class="tempo-cron__preset" x-on:click.outside="presetOpen = false" x-on:keydown.escape.window="presetOpen = false">
                    <button
                        type="button"
                        class="tempo-cron__mode tempo-cron__preset-trigger"
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
                    <div id="{{ $getId() }}-presets" class="tempo-cron__preset-panel" x-show="presetOpen" role="group" aria-label="{{ $labels['preset'] }}" x-cloak>
                        <template x-for="key in Object.keys(presets)" :key="key">
                            <button type="button" class="tempo-cron__preset-option" :aria-pressed="preset === key" x-text="presetLabel(key)" x-on:click="choosePreset(key); $refs.presetTrigger.focus()" @disabled($disabled)></button>
                        </template>
                    </div>
                </div>
            @endif
        </div>

        <div class="tempo-cron__visual" x-ref="visual" x-show="mode === 'visual'" x-on:click.outside="closePart()" x-on:keydown.escape.window="closePart(true)" x-on:resize.window="activePart && positionPopup()" x-cloak>
            <div class="tempo-cron__flow">
                <template x-for="part in visibleParts()" :key="part.name">
                    <button
                        type="button"
                        class="tempo-cron__part"
                        :class="{ 'tempo-cron__part--active': activePart === part.name }"
                        :aria-expanded="activePart === part.name"
                        :aria-controls="'{{ $getId() }}-editor-' + part.name"
                        x-on:click="togglePart(part, $event.currentTarget)"
                        :disabled="@js($disabled)"
                    >
                        <span class="tempo-cron__part-label" x-text="labels.fields[part.name]"></span>
                        <span class="tempo-cron__part-value" x-text="summary(part)"></span>
                    </button>
                </template>
            </div>

            <div class="tempo-cron__fields" x-show="activePart !== null" :class="{ 'tempo-cron__fields--above': popupAbove, 'tempo-cron__fields--narrow': gridColumns[activePart] <= 3, 'tempo-cron__fields--medium': gridColumns[activePart] === 4 }" :style="{ left: popupLeft + 'px' }" x-cloak>
                <template x-for="part in visibleParts()" :key="part.name">
                    <div class="tempo-cron__field" :id="'{{ $getId() }}-editor-' + part.name" x-show="activePart === part.name" x-cloak>
                        <div class="tempo-cron__field-header">
                            <span class="tempo-cron__label" x-text="labels.fields[part.name]"></span>
                            <div class="tempo-cron__field-actions">
                                <button
                                    type="button"
                                    class="tempo-cron__field-mode"
                                    :aria-label="labels.switch_to.replace('{mode}', part.mode === 'step' ? labels.multiple : labels.every)"
                                    :title="labels.switch_to.replace('{mode}', part.mode === 'step' ? labels.multiple : labels.every)"
                                    x-on:click="toggleMode(part)"
                                    :disabled="@js($disabled)"
                                >
                                    <span x-text="part.mode === 'step' ? labels.every : labels.multiple"></span>
                                    <span aria-hidden="true">⇄</span>
                                </button>
                                <button type="button" class="tempo-cron__close" x-on:click="closePart(true)" :aria-label="labels.close">×</button>
                            </div>
                        </div>
                        <div class="tempo-cron__grid" :style="{ '--tempo-cron-grid-columns': gridColumns[part.name] }" x-show="part.mode === 'step'" x-cloak role="group" :aria-label="labels.fields[part.name] + ' ' + labels.every">
                            <template x-for="option in options(part)" :key="option.value">
                                <button type="button" class="tempo-cron__grid-value" :aria-pressed="part.value === option.value" :disabled="@js($disabled)" x-on:click="part.value = option.value; updateVisual()" x-text="option.label"></button>
                            </template>
                        </div>
                        <div class="tempo-cron__grid" :style="{ '--tempo-cron-grid-columns': gridColumns[part.name] }" x-show="part.mode === 'multiple'" x-cloak role="group" :aria-label="labels.fields[part.name] + ' ' + labels.multiple">
                            <template x-for="option in options(part)" :key="option.value">
                                <button type="button" class="tempo-cron__grid-value" :aria-pressed="part.values.includes(option.value)" :aria-label="option.label" :disabled="@js($disabled) || (part.values.length === 1 && part.values.includes(option.value))" x-on:click="toggleValue(part, option.value)" x-text="gridLabel(part, option.value)"></button>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div x-show="mode === 'expression'" x-cloak>
            <input
                id="{{ $getId() }}-expression"
                class="tempo-cron__input"
                type="text"
                aria-label="{{ $labels['expression'] }}"
                inputmode="text"
                autocomplete="off"
                placeholder="* * * * *"
                x-model="raw"
                x-on:input="updateRaw($event.target.value)"
                :aria-invalid="raw !== '' && !isValid(raw)"
                @disabled($disabled)
            />
            <p class="tempo-cron__hint tempo-cron__hint--error" x-show="raw !== '' && !isValid(raw)">{{ $labels['invalid'] }}</p>
            <p class="tempo-cron__hint" x-show="raw !== '' && isValid(raw) && !canShowVisual()">{{ $labels['manual_only'] }}</p>
        </div>
        @if ($showDescription)
            <p class="tempo-cron__description" x-show="describe() !== ''" x-text="describe()" aria-live="polite" x-cloak></p>
        @endif
    </div>
</x-dynamic-component>

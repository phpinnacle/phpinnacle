@php
    use Filament\Support\Facades\FilamentAsset;
    use Filament\Support\View\ComponentAttributeBag;
    use Filament\Support\View\Components\BadgeComponent;
    use Illuminate\Support\Js;

    $disabled = $action->getSchemaComponent()->isDisabled();
    $valid = ! $action->getSchemaComponent()->getLivewire()->getErrorBag()->has($action->getSchemaComponent()->getStatePath());
@endphp

<x-filament::input.wrapper
    :disabled="$disabled"
    :valid="$valid"
    :class="$settings === [] ? 'inline-builder-empty' : ''"
    role="group"
    aria-label="{{ $action->getLabel() }}"
    data-inline-builder="{{ $action->getSchemaComponent()->getId() }}"
    x-load
    x-load-src="{{ FilamentAsset::getAlpineComponentSrc('inline-builder', 'phpinnacle/common') }}"
    :x-data="'inlineBuilder('.Js::from(['action' => $action->getName(), 'context' => $action->getContext(), 'disabled' => $disabled])->toHtml().')'"
    x-on:click="openFromSpace($event)"
>
    <div @class(['inline-builder-input', 'has-additions' => ! $disabled && $addable !== []])>
        <div class="inline-builder-add" x-ref="add">
            @if ($addable !== [])
                <x-filament::dropdown placement="bottom-start" teleport>
                    <x-slot name="trigger">
                        <x-filament::icon-button
                            :icon="$action->getIcon()"
                            :label="$action->getLabel()"
                            title=""
                            :disabled="$disabled"
                            color="gray"
                            size="sm"
                            class="inline-builder-add-button"
                        />
                    </x-slot>
                    <x-filament::dropdown.list>
                        @foreach ($addable as $block)
                            @php
                                $button = $action(['block' => $block->getName()]);
                            @endphp
                            {{ $button->grouped()->label($block->getLabel())->icon($block->getIcon()) }}
                        @endforeach
                    </x-filament::dropdown.list>
                </x-filament::dropdown>
            @else
                <x-filament::icon-button
                    :icon="$action->getIcon()"
                    :label="$action->getLabel()"
                    title=""
                    disabled
                    color="gray"
                    size="sm"
                    class="inline-builder-add-button"
                />
            @endif
        </div>
        @if ($settings === [])
            <span class="inline-builder-placeholder" aria-hidden="true">{{ $action->getSchemaComponent()->getPlaceholder() ?? __('phpinnacle-common::inline-builder.placeholder') }}</span>
        @endif
        <div
            class="inline-builder-items"
            x-ref="items"
            data-allowed-moves="{{ json_encode($allowedMoves, JSON_THROW_ON_ERROR) }}"
            @if ($action->getSchemaComponent()->isReorderable())
                x-sortable
                x-init="$nextTick(() => configureSorting($el))"
                x-on:end.stop="reorder($event)"
            @endif
        >
            @foreach ($settings as $index => $setting)
                @php
                    $block = $available[$setting['type']];
                    $hasSettings = $block->hasSchema();
                    $labelTag = $hasSettings ? 'button' : 'span';
                    $edit = $action(['block' => $block->getName(), 'index' => $index]);
                    $remove = $action(['block' => $block->getName(), 'index' => $index, 'remove' => true]);
                    $removeLabel = __('phpinnacle-common::inline-builder.remove', ['block' => $block->getLabel()]);
                @endphp
                <span
                    {{ (new ComponentAttributeBag)->class(['inline-builder-item', 'is-disabled' => $disabled])->color(BadgeComponent::class, $block->getColor()) }}
                    data-inline-builder-item
                    role="group"
                    data-field-interactive
                    aria-label="{{ $block->getLabel() }}"
                    x-sortable-item="{{ $index }}"
                    wire:key="{{ $action->getSchemaComponent()->getId() }}-{{ $action->getName() }}-{{ $index }}"
                >
                    <{{ $labelTag }}
                        class="inline-builder-item-label"
                        x-sortable-handle
                        x-on:keydown.alt.arrow-left.prevent="move(@js((string) $index), -1)"
                        x-on:keydown.alt.arrow-right.prevent="move(@js((string) $index), 1)"
                        @if ($hasSettings)
                            type="button"
                            wire:click="{{ $edit->getLivewireClickHandler() }}"
                            wire:loading.attr="disabled"
                            wire:target="{{ $edit->getLivewireClickHandler() }}"
                            @disabled($disabled)
                        @else
                            tabindex="{{ $disabled ? -1 : 0 }}"
                            aria-disabled="{{ $disabled ? 'true' : 'false' }}"
                        @endif
                    >
                        @if ($block->getIcon())
                            <x-filament::icon :icon="$block->getIcon()" />
                        @endif
                        <span class="inline-builder-item-text">{{ $block->getLabel() }}</span>
                    </{{ $labelTag }}>
                    @if ($action->getSchemaComponent()->isDeletable())
                        <button
                            type="button"
                            class="inline-builder-item-remove"
                            aria-label="{{ $removeLabel }}"
                            title="{{ $removeLabel }}"
                            wire:click="{{ $remove->getLivewireClickHandler() }}"
                            wire:loading.attr="disabled"
                            wire:target="{{ $remove->getLivewireClickHandler() }}"
                            @disabled($disabled)
                        >
                            <x-filament::icon icon="heroicon-m-x-mark" />
                        </button>
                    @endif
                </span>
            @endforeach
        </div>
    </div>
</x-filament::input.wrapper>

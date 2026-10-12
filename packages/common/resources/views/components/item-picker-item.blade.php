@php
    $hasChildren = !$resolved && $item->children !== [];
    $descriptionId = $item->description !== null ? $id . '-description' . $row['key'] : null;
    $badgesId = $item->badges !== [] ? $id . '-badges' . $row['key'] : null;
    $describedBy = implode(' ', array_filter([$descriptionId, $badgesId]));
@endphp

<div
    wire:key="{{ $id }}-item{{ $row['key'] }}"
    data-item-key="{{ $row['key'] }}"
    role="treeitem"
    aria-level="{{ $row['depth'] + 1 }}"
    aria-label="{{ $item->label }}"
    @if ($describedBy !== '')
        aria-describedby="{{ $describedBy }}"
    @endif
    @if ($branchesSelectable || !$hasChildren)
        x-bind:aria-selected="selected(@js($item->value))"
    @endif
    @if ($hasChildren)
        x-bind:aria-expanded="expanded.includes(@js($row['key']))"
    @endif
    aria-disabled="{{ $disabled || $item->disabled ? 'true' : 'false' }}"
    x-bind:tabindex="@js($disabled) ? -1 : tabIndex(@js($row['key']))"
    @if ($resolved)
        x-show="inlineVisible(@js($item->value))"
    @elseif ($disabled && !$select)
        x-show="selected(@js($item->value))"
    @else
        x-show="visible({ ancestors: @js($row['ancestors']) }) && !inlineVisible(@js($item->value))"
    @endif
    x-on:focus="active = @js($row['key'])"
    x-on:click="activate(@js($row['key']))"
    x-on:keydown="keydown($event, @js($row['key']))"
    x-bind:class="{ 'is-selected': selected(@js($item->value)) }"
    style="--item-depth: {{ $row['depth'] }}; {{ \Filament\Support\get_color_css_variables($item->color, [50, 400, 600]) }}"
    class="common-item-picker__item"
>
    @if ($select && $hasResolver)
        <template data-item-selection>
            @include('phpinnacle-common::components.item-picker-selection', ['rows' => [$row]])
        </template>
    @endif
    @include('phpinnacle-common::components.item-picker-content', [
        'item' => $item,
        'descriptionId' => $descriptionId,
        'badgesId' => $badgesId,
        'showDescription' => true,
        'showBadges' => true,
    ])
    @if ($hasChildren)
        <button
            type="button"
            tabindex="-1"
            @disabled($disabled)
            x-on:click.stop="toggleBranch(@js($row['key']))"
            x-bind:aria-expanded="expanded.includes(@js($row['key']))"
            aria-label="{{ __('phpinnacle-common::item-picker.toggle', ['label' => $item->label]) }}"
            class="common-item-picker__branch"
        >
            <span x-show="!expanded.includes(@js($row['key']))">
                <x-filament::icon icon="heroicon-m-chevron-right" />
            </span>
            <span x-cloak x-show="expanded.includes(@js($row['key']))">
                <x-filament::icon icon="heroicon-m-chevron-down" />
            </span>
        </button>
    @endif
</div>

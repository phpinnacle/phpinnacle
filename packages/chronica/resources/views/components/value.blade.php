@props(['value'])

@if ($value['badge'])
    <x-filament::badge :color="$value['color'] ?? 'gray'" :icon="$value['icon']">
        {{ $value['text'] }}
    </x-filament::badge>
@elseif ($value['icon'] !== null)
    <span
        role="img"
        aria-label="{{ $value['text'] }}"
        title="{{ $value['text'] }}"
    >
        <x-filament::icon
            :icon="$value['icon']"
            :style="\Filament\Support\get_color_css_variables($value['color'], [400, 600])"
            class="size-5 text-[var(--color-600)] dark:text-[var(--color-400)]"
        />
    </span>
@else
    {{ $value['text'] }}
@endif

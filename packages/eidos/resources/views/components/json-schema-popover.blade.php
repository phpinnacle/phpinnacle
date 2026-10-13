@props(['tooltip', 'popover', 'label' => 'tooltip'])

<span
    x-data="{ get tooltip() { return {{ $tooltip }}(source, @js(__('phpinnacle-eidos::json-schema'))) }, get popover() { return {{ $popover }}(source, @js(__('phpinnacle-eidos::json-schema'))) } }"
    x-tooltip="{ content: popover, theme: $store.theme + ' json-schema', allowHTML: true }"
    :aria-label="{{ $label }}"
    role="img"
    tabindex="0"
    x-on:click.stop
    x-on:mousedown.stop
    x-on:keyup.enter.stop
    x-on:keyup.space.stop
    x-on:keydown.enter.stop.prevent
    x-on:keydown.space.stop.prevent
    {{ $attributes }}
>{{ $slot }}</span>

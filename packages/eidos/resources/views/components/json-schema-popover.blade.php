@props(['tooltip', 'popover', 'label' => 'tooltip', 'interactive' => false, 'descriptions' => 'true'])

<span
    x-data="{ get tooltip() { return {{ $tooltip }}(source, @js(__('phpinnacle-eidos::json-schema'))) }, get popover() { return {{ $popover }}(source, @js(__('phpinnacle-eidos::json-schema')), {{ $descriptions }}) } }"
    x-tooltip="{ content: popover, theme: $store.theme + @js($interactive ? ' json-schema json-schema-array' : ' json-schema'), allowHTML: true, interactive: @js($interactive), trigger: @js($interactive ? 'click' : 'mouseenter focus'), placement: @js($interactive ? 'left' : 'top') }"
    :aria-label="{{ $label }}"
    role="{{ $interactive ? 'button' : 'img' }}"
    tabindex="0"
    x-on:click.stop
    x-on:mousedown.stop
    x-on:keyup.enter.stop
    x-on:keyup.space.stop
    x-on:keydown.enter.stop.prevent="{{ $interactive ? '$el.click()' : '' }}"
    x-on:keydown.space.stop.prevent="{{ $interactive ? '$el.click()' : '' }}"
    {{ $attributes }}
>{{ $slot }}</span>

@foreach ($rows as $row)
    @php $item = $row['node']; @endphp
    @if ($select)
        <span
            x-cloak
            x-show="selected(@js($item->value))"
            data-selected-value="{{ $item->value }}"
            x-bind:class="{ 'is-hidden': multiple && !visibleValues.includes(@js($item->value)) }"
            x-bind:aria-hidden="multiple && !visibleValues.includes(@js($item->value))"
            class="common-item-picker__content"
            style="{{ \Filament\Support\get_color_css_variables($item->color, [50, 400, 600]) }}"
        >
            @include('phpinnacle-common::components.item-picker-content', [
                'item' => $item,
                'showDescription' => !$multiple,
                'showBadges' => false,
                'descriptionId' => null,
                'badgesId' => null,
            ])
        </span>
    @else
        @include('phpinnacle-common::components.item-picker-item', ['item' => $item, 'row' => $row, 'resolved' => true])
    @endif
@endforeach

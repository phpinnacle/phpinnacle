@php use PHPinnacle\Common\Forms\Item; @endphp

@foreach ($rows as $row)
    @php $item = $row['node']; @endphp
    @if ($item instanceof Item)
        @include('phpinnacle-common::components.item-picker-item', ['item' => $item, 'row' => $row, 'resolved' => false])
    @else
        <div
            wire:key="{{ $id }}-group{{ $row['key'] }}"
            x-show="visible({ ancestors: @js($row['ancestors']) })"
            role="presentation"
            class="common-item-picker__group"
        >
            <span class="common-item-picker__heading">
                <span>{{ $item->label }}</span>
                @if ($item->badges !== [])
                    @include('phpinnacle-common::components.item-picker-badges', ['badges' => $item->badges, 'badgesId' => null])
                @endif
            </span>
            @if ($item->description !== null)
                @if ($isCompact())
                    <span aria-hidden="true">·</span>
                @endif
                <p>{{ $item->description }}</p>
            @endif
        </div>
    @endif
@endforeach

@if ($item->image !== null)
    <span class="common-item-picker__icon">
        <x-filament::avatar :src="$item->image" alt="" :circular="$isCircular()" size="common-item-picker__image" />
    </span>
@elseif ($item->icon !== null)
    <span class="common-item-picker__icon"><x-filament::icon :icon="$item->icon" /></span>
@endif
<span class="common-item-picker__text">
    <span class="common-item-picker__heading">
        <span class="common-item-picker__label">{{ $item->label }}</span>
        @if ($showBadges && $item->badges !== [])
            @include('phpinnacle-common::components.item-picker-badges', ['badges' => $item->badges, 'badgesId' => $badgesId])
        @endif
    </span>
    @if ($showDescription && $item->description !== null)
        <span @if ($descriptionId !== null) id="{{ $descriptionId }}" @endif class="common-item-picker__description">{{ $item->description }}</span>
    @endif
</span>

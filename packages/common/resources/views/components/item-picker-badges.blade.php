<span @if ($badgesId !== null) id="{{ $badgesId }}" @endif class="common-item-picker__badges">
    @foreach ($badges as $badge)
        <x-filament::badge :color="$badge->getColor()" :icon="$badge->getIcon()" size="xs" :title="$badge->getLabel()">
            {{ $badge->getLabel() }}
        </x-filament::badge>
    @endforeach
</span>

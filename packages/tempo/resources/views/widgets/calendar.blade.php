<x-filament-widgets::widget class="fi-wi-calendar">
    <x-filament::section :heading="$heading">
        <div
            wire:ignore
            x-load
            x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('tempo-calendar', 'phpinnacle/tempo') }}"
            x-load-css="[@js(\Filament\Support\Facades\FilamentAsset::getStyleHref('tempo-calendar', 'phpinnacle/tempo'))]"
            x-on:tempo-calendar-refresh.window="if (! $event.detail.componentId || $event.detail.componentId === @js($this->getId())) refresh()"
            x-data="tempoCalendar({
                initialView: @js($initialView),
                availableViews: @js($availableViews),
                locale: @js($locale),
                weekStartsOn: @js($weekStartsOn),
                showWeekends: @js($showWeekends),
                showEventTime: @js($showEventTime),
                maxEventsPerDay: @js($maxEventsPerDay),
                eventSourceKeys: @js($eventSourceKeys),
            })"
            class="tempo-calendar"
        >
            @if (count($eventSources) > 1)
                <div
                    class="tempo-calendar__filters"
                    role="group"
                    aria-label="{{ __('phpinnacle-tempo::calendar.values.filters') }}"
                >
                    @foreach ($eventSources as $eventSource)
                    <button
                        type="button"
                        class="tempo-calendar__filter"
                        wire:key="tempo-calendar-source-{{ $eventSource['key'] }}"
                        @if ($eventSource['colorStyle'] !== null) style="{{ $eventSource['colorStyle'] }}" @endif
                        :class="{ 'tempo-calendar__filter--active': eventSourceIsSelected(@js($eventSource['key'])) }"
                        :aria-pressed="eventSourceIsSelected(@js($eventSource['key']))"
                        x-on:click="toggleEventSource(@js($eventSource['key']))"
                    >
                        @if ($eventSource['icon'] !== null)
                            <x-filament::icon :icon="$eventSource['icon']" class="tempo-calendar__filter-icon" />
                        @endif
                        <span>{{ $eventSource['label'] }}</span>
                    </button>
                    @endforeach
                </div>
            @endif
            <div
                x-cloak
                x-show="status === 'error' || status === 'invalid_range'"
                class="tempo-calendar__notice tempo-calendar__notice--danger"
            >
                {{ __('phpinnacle-tempo::calendar.values.load_failed') }}
            </div>
            <div x-cloak x-show="truncated" class="tempo-calendar__notice tempo-calendar__notice--warning">
                {{ __('phpinnacle-tempo::calendar.values.truncated') }}
            </div>
            <div x-ref="calendar" class="tempo-calendar__canvas"></div>
        </div>
    </x-filament::section>

    <x-filament-actions::modals />
</x-filament-widgets::widget>

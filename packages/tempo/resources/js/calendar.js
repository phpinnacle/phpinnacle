import { Calendar } from 'fullcalendar'
import allLocales from 'fullcalendar/locales-all'
import dayGridPlugin from 'fullcalendar/daygrid'
import listPlugin from 'fullcalendar/list'
import timeGridPlugin from 'fullcalendar/timegrid'
import breezyThemePlugin from 'fullcalendar/themes/breezy'

const viewButtons = {
    dayGridMonth: 'dayGridMonth',
    timeGridWeek: 'timeGridWeek',
    timeGridDay: 'timeGridDay',
    listWeek: 'listWeek',
}

export function viewSwitcher(views) {
    return views.length > 1 ? views.join(',') : ''
}

export function eventIsActionable(event) {
    return Boolean(event.extendedProps.actionable)
}

export function eventActionArguments(event) {
    return {
        event: {
            id: event.id,
            title: event.title,
            start: event.startStr,
            end: event.endStr || null,
            allDay: event.allDay,
            viewable: Boolean(event.extendedProps.viewable),
            color: event.backgroundColor || null,
            url: event.extendedProps.url || null,
        },
    }
}

export default function tempoCalendar({
    initialView = 'dayGridMonth',
    availableViews = ['dayGridMonth'],
    locale = 'en',
    weekStartsOn = 1,
    showWeekends = true,
    showEventTime = true,
    maxEventsPerDay = 3,
    eventSourceKeys = [],
} = {}) {
    return {
        calendar: null,
        status: 'ready',
        truncated: false,
        selectedEventSources: [...eventSourceKeys],

        init() {
            const views = availableViews.filter((view) => viewButtons[view])

            this.calendar = new Calendar(this.$refs.calendar, {
                plugins: [breezyThemePlugin, dayGridPlugin, timeGridPlugin, listPlugin],
                locales: allLocales,
                locale,
                initialView: views.includes(initialView) ? initialView : (views[0] ?? 'dayGridMonth'),
                firstDay: Number(weekStartsOn) === 0 ? 0 : 1,
                weekends: Boolean(showWeekends),
                displayEventTime: Boolean(showEventTime),
                dayMaxEventRows: Number(maxEventsPerDay || 3),
                height: '100%',
                headerToolbar: {
                    left: 'prev,today,next',
                    center: 'title',
                    right: viewSwitcher(views),
                },
                events: async (info, success, failure) => {
                    try {
                        const response = await this.$wire.fetchCalendarEvents(
                            info.startStr,
                            info.endStr,
                            this.selectedEventSources
                        )
                        this.status = response.status ?? 'ready'
                        this.truncated = Boolean(response.truncated)
                        success(response.events ?? [])
                    } catch (error) {
                        this.status = 'error'
                        this.truncated = false
                        failure(error)
                    }
                },
                eventClick: (info) => {
                    if (!eventIsActionable(info.event)) {
                        return
                    }

                    info.jsEvent.preventDefault()
                    this.$wire.mountAction('record', eventActionArguments(info.event))
                },
            })

            this.calendar.render()
        },

        refresh() {
            this.calendar?.refetchEvents()
        },

        eventSourceIsSelected(key) {
            return this.selectedEventSources.includes(key)
        },

        toggleEventSource(key) {
            this.selectedEventSources = this.eventSourceIsSelected(key)
                ? this.selectedEventSources.filter((selectedKey) => selectedKey !== key)
                : [...this.selectedEventSources, key]

            this.refresh()
        },

        destroy() {
            this.calendar?.destroy()
            this.calendar = null
        },
    }
}

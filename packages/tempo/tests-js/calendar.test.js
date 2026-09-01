import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import test from 'node:test'
import tempoCalendar, { eventActionArguments, eventIsActionable, viewSwitcher } from '../resources/js/calendar.js'

test('fills the available height inside a Filament widget', () => {
    const css = readFileSync(new URL('../resources/css/calendar.css', import.meta.url), 'utf8')

    assert.ok(css.includes(`.fi-wi-calendar .fi-section-content-ctn,
.fi-wi-calendar .fi-section-content {
    display: flex;
    min-height: 0;
}

.fi-wi-calendar .fi-section-content {
    flex: 1;
    overflow: hidden;
}`))
})

test('hides the view switcher when only one calendar view is available', () => {
    assert.equal(viewSwitcher(['dayGridMonth']), '')
    assert.equal(viewSwitcher([]), '')
})

test('shows the view switcher when multiple calendar views are available', () => {
    assert.equal(viewSwitcher(['dayGridMonth', 'listWeek']), 'dayGridMonth,listWeek')
})

test('makes viewable events clickable', () => {
    const event = { extendedProps: { actionable: true } }

    assert.equal(eventIsActionable(event), true)
    assert.equal(eventIsActionable({ extendedProps: { actionable: false } }), false)
})

test('builds serializable action arguments from a calendar event', () => {
    assert.deepEqual(eventActionArguments({
        title: 'Planning',
        startStr: '2026-08-09T10:00:00+03:00',
        endStr: '',
        allDay: false,
        backgroundColor: '#3b82f6',
        url: 'https://example.com/planning/42',
        id: 'planning-42',
        extendedProps: {
            actionable: true,
            viewable: true,
            url: 'https://example.com/planning/42',
        },
    }), {
        event: {
            id: 'planning-42',
            title: 'Planning',
            start: '2026-08-09T10:00:00+03:00',
            end: null,
            allDay: false,
            viewable: true,
            color: '#3b82f6',
            url: 'https://example.com/planning/42',
        },
    })
})

test('delegates responsive sizing to FullCalendar 7', () => {
    const component = tempoCalendar()

    assert.equal(component.resize, undefined)
    assert.equal(component.observer, undefined)
})

test('refetches events without recreating the calendar', () => {
    const component = tempoCalendar()
    let refetches = 0
    component.calendar = {
        refetchEvents() {
            refetches++
        },
    }

    component.refresh()

    assert.equal(refetches, 1)
})

test('selects all event sources by default and refetches after filtering', () => {
    const component = tempoCalendar({
        eventSourceKeys: ['appointments', 'deadlines'],
    })
    let refetches = 0
    component.calendar = {
        refetchEvents() {
            refetches++
        },
    }

    assert.deepEqual(component.selectedEventSources, ['appointments', 'deadlines'])
    assert.equal(component.eventSourceIsSelected('appointments'), true)

    component.toggleEventSource('appointments')

    assert.deepEqual(component.selectedEventSources, ['deadlines'])
    assert.equal(component.eventSourceIsSelected('appointments'), false)
    assert.equal(refetches, 1)
})

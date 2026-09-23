import assert from 'node:assert/strict'
import test from 'node:test'

import tempoCron, { describe, isValid, parseVisual } from '../resources/js/cron-expression.js'

const presets = { weekly: '0 9 * * 1' }

test('presets and visual controls keep a five-field string as the field state', () => {
    const editor = tempoCron({ state: null, labels: {}, presets })
    editor.$nextTick = () => {}

    editor.preset = 'weekly'
    editor.choosePreset()

    assert.equal(editor.state, presets.weekly)
    assert.equal(editor.mode, 'visual')

    editor.toggleMode(editor.parts[0])
    editor.parts[0].value = '15'
    editor.updateVisual()

    assert.equal(editor.state, '*/15 9 * * 1')
    assert.equal(isValid(editor.state), true)
})

test('starts in the configured mode and uses only PHP-provided presets', () => {
    const customPresets = { 'Late weekdays': '0 23 ? * MON-FRI' }
    const editor = tempoCron({
        state: '0 9 * * 1', labels: {}, presets: customPresets, defaultMode: 'expression',
    })

    editor.syncState(editor.state)
    assert.equal(editor.mode, 'expression')
    assert.equal(editor.preset, '')

    editor.preset = 'Late weekdays'
    editor.choosePreset()
    assert.equal(editor.state, '0 23 ? * MON-FRI')
    assert.equal(editor.mode, 'expression')
    assert.equal(editor.preset, 'Late weekdays')

    editor.showVisual()
    assert.equal(editor.mode, 'visual')
    editor.syncState('0 9 * * 1')
    assert.equal(editor.mode, 'visual')

    editor.syncState(null)
    assert.equal(editor.mode, 'expression')
})

test('external state changes still apply after a local write', () => {
    const local = '0 9 * * 1'
    const external = '0 11 * * 1'
    const editor = tempoCron({ state: '* * * * *', labels: {} })
    let onStateChange

    editor.$watch = (property, callback) => {
        assert.equal(property, 'state')
        onStateChange = callback
    }
    editor.init()

    editor.write(local)
    editor.activePart = 'hour'
    onStateChange(local)
    assert.equal(editor.activePart, 'hour')

    editor.state = external
    onStateChange(external)
    assert.equal(editor.raw, external)

    editor.state = local
    onStateChange(local)
    assert.equal(editor.raw, local)
    assert.deepEqual(editor.parts[1].values, ['9'])
})

test('hidden weekday remains editable as expression and survives visual edits', () => {
    const expression = '0 23 L * MON-FRI'
    const editor = tempoCron({ state: expression, labels: {}, showDayOfWeek: false })

    editor.syncState(expression)
    assert.equal(editor.visibleParts().length, 4)
    assert.equal(editor.mode, 'visual')

    editor.parts[1].values = ['22']
    editor.updateVisual()
    assert.equal(editor.state, '0 22 L * MON-FRI')

    editor.updateRaw('0 23 L * */99')
    assert.equal(editor.canShowVisual(), true)
    editor.showVisual()
    editor.parts[1].values = ['21']
    editor.updateVisual()
    assert.equal(editor.state, '0 21 L * */99')
    assert.equal(isValid(editor.state), true)
})

test('a single value uses the selected-values editor', () => {
    const expression = '0 9 * * 1'
    const editor = tempoCron({ state: expression, labels: {} })

    editor.syncState(expression)
    assert.equal(editor.parts[4].mode, 'multiple')
    assert.deepEqual(editor.parts[4].values, ['1'])

    editor.toggleValue(editor.parts[4], '3')
    assert.equal(editor.state, '0 9 * * 1,3')
    editor.toggleValue(editor.parts[4], '3')
    assert.equal(editor.state, expression)
    editor.toggleValue(editor.parts[4], '1')
    assert.equal(editor.state, expression)
})

test('selected values expand and compact lists, ranges, and stepped ranges', () => {
    const expression = '5,20 8-18/2 * 1,6 1-5'
    const editor = tempoCron({ state: expression, labels: {} })

    editor.syncState(expression)

    assert.equal(editor.mode, 'visual')
    assert.equal(editor.raw, expression)
    assert.equal(editor.canShowVisual(), true)
    assert.equal(isValid(expression), true)
    assert.deepEqual(editor.parts.map(part => part.mode), ['multiple', 'multiple', 'step', 'multiple', 'multiple'])
    assert.deepEqual(editor.parts[1].values, ['8', '10', '12', '14', '16', '18'])
    assert.deepEqual(editor.parts[4].values, ['1', '2', '3', '4', '5'])
    editor.updateVisual()
    assert.equal(editor.state, expression)

    editor.toggleValue(editor.parts[0], '35')
    assert.equal(editor.state, '5-35/15 8-18/2 * 1,6 1-5')
    editor.toggleValue(editor.parts[0], '20')
    assert.equal(editor.state, '5,35 8-18/2 * 1,6 1-5')
    editor.toggleValue(editor.parts[4], '3')
    assert.equal(editor.state, '5,35 8-18/2 * 1,6 1-2,4-5')
    editor.toggleValue(editor.parts[4], '3')
    assert.equal(editor.state, '5,35 8-18/2 * 1,6 1-5')
})

test('mixed lists are visual while unsupported values remain editable as text', () => {
    const expression = '5,20-30 * * * *'
    const editor = tempoCron({ state: expression, labels: {} })

    editor.syncState(expression)
    assert.equal(editor.mode, 'visual')
    assert.equal(editor.raw, expression)
    assert.equal(editor.canShowVisual(), true)
    assert.deepEqual(editor.parts[0].values, ['5', ...Array.from({ length: 11 }, (_, index) => String(index + 20))])
    editor.updateVisual()
    assert.equal(editor.state, expression)
    assert.equal(parseVisual('*/99 * * * *'), null)
    assert.equal(parseVisual('01 * * * *'), null)
    assert.equal(parseVisual('5,5 * * * *'), null)

    editor.syncState('*/99 * * * *')
    assert.equal(editor.mode, 'expression')
    assert.equal(editor.canShowVisual(), false)

    editor.syncState(null)
    assert.equal(editor.mode, 'visual')
    assert.equal(editor.raw, '')
})

test('invalid values do not enter the visual editor', () => {
    for (const value of ['60 * * * *', '*/0 * * * *', '*/01 * * * *', '0/15 * * * *', '* 24 * * *', '* * 0 * *', '* * * * 8', '* * * *']) {
        assert.equal(isValid(value), value === '* * * * *', value)
    }
})

test('every interval one uses the wildcard expression', () => {
    const editor = tempoCron({ state: '* * * * *', labels: { every: 'Every', step: 'Every N' } })
    editor.$nextTick = () => {}

    editor.syncState(editor.state)
    assert.equal(editor.parts[0].mode, 'step')
    assert.equal(editor.parts[0].value, '1')
    assert.equal(editor.summary(editor.parts[0]), 'Every')
    assert.equal(editor.options(editor.parts[0])[0].value, '1')

    editor.parts[0].value = '2'
    editor.updateVisual()
    assert.equal(editor.state, '*/2 * * * *')
    assert.equal(editor.summary(editor.parts[0]), 'Every 2')

    editor.parts[0].value = '1'
    editor.updateVisual()
    assert.equal(editor.state, '* * * * *')

    editor.syncState('*/1 * * * *')
    assert.equal(editor.parts[0].mode, 'step')
    assert.equal(editor.parts[0].value, '1')
    editor.updateVisual()
    assert.equal(editor.state, '* * * * *')

    editor.toggleMode(editor.parts[0])
    assert.equal(editor.state, '0 * * * *')
    editor.toggleMode(editor.parts[0])
    assert.equal(editor.state, '* * * * *')
})

test('named days and the unspecified day marker are valid and editable', () => {
    const expression = '0 23 ? * MON-FRI'

    assert.equal(isValid(expression), true)
    assert.deepEqual(parseVisual(expression)?.[4].values, ['1', '2', '3', '4', '5'])
    assert.equal(isValid('0 23 ? * ?'), false)
    assert.equal(isValid('0 23 ? * MON-FUNDAY'), false)
})

test('last day of month is selectable as a separate day value', () => {
    const expression = '0 23 L * *'
    const editor = tempoCron({ state: expression, labels: { last: 'Last' } })

    assert.equal(isValid(expression), true)
    assert.equal(isValid('0 23 L * ?'), true)
    assert.equal(isValid('0 23 1,L * *'), false)
    assert.equal(isValid('0 23 L-1 * *'), false)
    assert.equal(isValid('0 23 * L *'), false)
    assert.deepEqual(parseVisual(expression)?.[2].values, ['L'])

    editor.syncState(expression)
    assert.equal(editor.mode, 'visual')
    assert.equal(editor.summary(editor.parts[2]), 'Last')
    assert.deepEqual(editor.options(editor.parts[2]).at(-1), { value: 'L', label: 'Last' })

    editor.toggleValue(editor.parts[2], '1')
    assert.equal(editor.state, '0 23 1 * *')
    editor.toggleValue(editor.parts[2], 'L')
    assert.equal(editor.state, expression)

    assert.equal(describe(expression, {
        locale: 'en',
        description: { at_time: 'At {value}', last_day: 'on the last day of the month' },
    }), 'At 23:00 on the last day of the month.')
})

test('describes the user expression and updates after a visual change', () => {
    const labels = {
        locale: 'en',
        description: {
            every_minute: 'Every minute', every_n_minutes: 'Every {value} minutes', every_hour: 'Every hour',
            at_time: 'At {value}', at_minute_every_hour: 'At minute {value} of every hour',
            minutes_every_hour: 'At minutes {value} of every hour',
            every_minute_at_hours: 'Every minute during hours {value}',
            minutes_at_hours: 'At minutes {minutes} during hours {hours}',
            weekdays: 'on weekdays', weekdays_list: 'on {value}',
            day: 'on day {value} of the month', days: 'on days {value} of the month',
            day_or_weekday: '{day} or {weekday}', months: 'in {value}',
        },
        short_weekdays: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
        months: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
    }
    const editor = tempoCron({ state: '0 23 ? * MON-FRI', labels })
    editor.syncState(editor.state)

    assert.equal(describe(editor.state, labels), 'At 23:00 on weekdays.')
    editor.toggleValue(editor.parts[4], '5')
    assert.equal(describe(editor.state, labels), 'At 23:00 on Mon–Thu.')
    assert.equal(describe('invalid', labels), '')
})

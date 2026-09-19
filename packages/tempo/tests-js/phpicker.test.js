import assert from 'node:assert/strict'
import test from 'node:test'
import phpPicker from '../resources/js/phpicker.js'

function datePicker(maskFormat = 'DD.MM.YYYY', mode = 'date') {
    const component = phpPicker({ mode, maskFormat })
    component.mask = { value: '' }

    return component
}

for (const value of ['07.09.2026', '09.07.2026', '21.09.2026']) {
    test(`preserves the configured day and month when syncing ${value}`, () => {
        const component = datePicker()

        component.syncState(value)
        assert.equal(component.mask.value, value)

        component.syncState(component.mask.value)
        assert.equal(component.mask.value, value)
    })
}

test('formats ISO state and calendar selections without changing their date', () => {
    const component = datePicker()

    component.syncState('2026-09-07')
    assert.equal(component.mask.value, '07.09.2026')

    component.syncState(new Date(2026, 8, 7))
    assert.equal(component.mask.value, '07.09.2026')
})

test('preserves localized datetime state', () => {
    const component = datePicker('DD.MM.YYYY HH:mm', 'datetime')

    component.syncState('07.09.2026 14:35')
    assert.equal(component.mask.value, '07.09.2026 14:35')
})

test('preserves time-only state', () => {
    const component = datePicker('HH:mm', 'time')

    component.syncState('14:35')
    assert.equal(component.mask.value, '14:35')
})

test('honors a month-first display format', () => {
    const component = datePicker('MM/DD/YYYY')

    component.syncState('09/07/2026')
    assert.equal(component.mask.value, '09/07/2026')
    assert.equal(component.parseDate(component.mask.value).getMonth(), 8)
    assert.equal(component.parseDate(component.mask.value).getDate(), 7)
})

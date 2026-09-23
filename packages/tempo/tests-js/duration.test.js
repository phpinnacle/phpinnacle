import assert from 'node:assert/strict'
import test from 'node:test'

import tempoDuration, { formatDuration, parseDuration } from '../resources/js/duration.js'

test('duration text converts to seconds without losing its visual parts', () => {
    const editor = tempoDuration({ state: 95400, labels: {}, storageFormat: 'seconds' })
    editor.syncState(editor.state)

    assert.equal(editor.raw, '1d 2h 30m')
    assert.deepEqual(editor.parts, { day: '1', hour: '2', minute: '30', second: '0' })

    editor.showText()
    editor.updateTextPart(editor.units[0], '0')
    editor.updateTextPart(editor.units[1], '1')
    assert.equal(editor.state, 5400)
    assert.equal(editor.raw, '1h 30m')
    editor.showVisual()
    assert.equal(editor.parts.hour, '1')
    assert.equal(editor.parts.minute, '30')

    editor.updatePart(editor.units[2], '45')
    assert.equal(editor.state, 6300)
})

test('string mode stores canonical visual and masked text changes', () => {
    const editor = tempoDuration({ state: '90m', labels: {}, storageFormat: 'string', presets: { hour: '1h' } })
    editor.syncState(editor.state)

    assert.equal(editor.raw, '90m')
    assert.equal(editor.parts.hour, '1')
    assert.equal(editor.parts.minute, '30')

    editor.choosePreset('hour')
    assert.equal(editor.state, '1h')

    editor.showText()
    editor.updateTextPart(editor.units[2], '30')
    assert.equal(editor.state, '1h 30m')

    editor.showVisual()
    editor.updatePart(editor.units[2], '45')
    assert.equal(editor.state, '1h 45m')
})

test('invalid external state stays visible for validation', () => {
    for (const storageFormat of ['seconds', 'string']) {
        const editor = tempoDuration({ state: '1 month', labels: {}, storageFormat })
        editor.syncState(editor.state)

        assert.equal(editor.raw, '1 month')
        assert.equal(editor.mode, 'text')
        assert.equal(editor.canShowVisual(), false)
    }
})

test('visual edits preserve invalid values for form validation', () => {
    const editor = tempoDuration({ state: 0, labels: {} })
    editor.syncState(editor.state)

    editor.updatePart(editor.units[2], 'bad')
    assert.equal(editor.state, 'badm')

    editor.updatePart(editor.units[2], '5')
    assert.equal(editor.state, 300)

    editor.updatePart(editor.units[0], String(Math.floor(Number.MAX_SAFE_INTEGER / 86400)))
    editor.updatePart(editor.units[1], '24')
    assert.equal(editor.state, '24h')
})

test('external state changes apply after local edits', () => {
    const editor = tempoDuration({ state: 60, labels: {} })
    let onStateChange
    editor.$watch = (property, callback) => {
        assert.equal(property, 'state')
        onStateChange = callback
    }
    editor.init()

    editor.updatePart(editor.units[2], '2')
    onStateChange(120)
    assert.equal(editor.raw, '2m')

    editor.state = 180
    onStateChange(180)
    assert.equal(editor.raw, '3m')

    editor.state = 120
    onStateChange(120)
    assert.equal(editor.raw, '2m')
})

test('parsing rejects unsupported units and unsafe totals', () => {
    assert.equal(parseDuration('0s'), 0)
    assert.equal(parseDuration('1h30m'), 5400)
    assert.equal(parseDuration('1 month'), null)
    assert.equal(parseDuration('1.5h'), null)
    assert.equal(parseDuration('01h'), null)
    assert.equal(parseDuration('9007199254740992s'), null)
    assert.equal(formatDuration(0), '0s')
})

test('configured units limit both editors and quick values', () => {
    const editor = tempoDuration({
        state: 5400, labels: { suffixes: { minute: 'м', second: 'с' } }, enabledUnits: ['minute', 'second'],
        presets: { fifteen_minutes: '15m', hour: '1h' },
        quickValues: { minute: [0, 1, 5], second: [0, 15, 30] },
    })
    editor.syncState(editor.state)

    assert.deepEqual(editor.visibleUnits().map(unit => unit.name), ['minute', 'second'])
    assert.equal(editor.raw, '90m')
    assert.equal(editor.parts.minute, '90')
    assert.equal(editor.textPlaceholder(), '30м 15с')
    assert.equal(editor.displaySuffix(editor.units[2]), 'м')
    assert.deepEqual(editor.quickValues(editor.units[2]), [0, 1, 5])
    assert.deepEqual(editor.quickValues(editor.units[3]), [0, 15, 30])
    assert.deepEqual(editor.presetKeys(), ['fifteen_minutes'])
    assert.equal(parseDuration('1h', editor.enabledUnits), null)

    editor.updatePart(editor.units[3], '30')
    assert.equal(editor.state, 5430)
    assert.equal(editor.raw, '90m 30s')

    editor.state = '1h'
    editor.syncState(editor.state)
    assert.equal(editor.state, '1h')
    assert.equal(editor.canShowVisual(), false)
})

test('text segments write canonical values and clear the field', () => {
    const editor = tempoDuration({ state: 5400, labels: {}, enabledUnits: ['minute', 'second'] })
    editor.syncState(editor.state)
    editor.showText()

    assert.equal(editor.parts.minute, '90')
    assert.equal(editor.parts.second, '0')

    editor.updateTextPart(editor.units[2], '2')
    editor.updateTextPart(editor.units[3], '30')
    assert.equal(editor.state, 150)
    assert.equal(editor.raw, '2m 30s')

    editor.updateTextPart(editor.units[3], 'bad')
    assert.equal(editor.state, '2m bads')
    assert.equal(editor.canShowVisual(), false)

    editor.updateTextPart(editor.units[3], '')
    editor.updateTextPart(editor.units[2], '')
    assert.equal(editor.state, null)
    assert.equal(editor.raw, '')

    const stringEditor = tempoDuration({ state: '90m', labels: {}, storageFormat: 'string', enabledUnits: ['minute', 'second'] })
    stringEditor.syncState(stringEditor.state)
    stringEditor.showText()
    stringEditor.updateTextPart(stringEditor.units[3], '15')
    assert.equal(stringEditor.state, '90m 15s')
})

test('one text input protects translated suffixes while editing and navigating', () => {
    const editor = tempoDuration({
        state: 5400,
        labels: { suffixes: { minute: 'м', second: 'с' } },
        enabledUnits: ['minute', 'second'],
    })
    editor.syncState(editor.state)
    editor.showText()

    const input = {
        value: editor.textValue(),
        selectionStart: 0,
        selectionEnd: 2,
        setSelectionRange(start, end) { this.selectionStart = start; this.selectionEnd = end },
    }
    let prevented = false
    const preventDefault = () => { prevented = true }

    editor.handleTextBeforeInput({ inputType: 'insertText', data: '2', cancelable: true, target: input, preventDefault })
    assert.equal(prevented, true)
    assert.equal(input.value, '2м 0с')
    assert.equal(editor.state, 120)

    editor.handleTextKeydown({ key: ' ', target: input, preventDefault })
    assert.deepEqual([input.selectionStart, input.selectionEnd], [3, 4])

    editor.handleTextBeforeInput({ inputType: 'insertText', data: '30', cancelable: true, target: input, preventDefault })
    assert.equal(input.value, '2м 30с')
    assert.equal(editor.state, 150)

    input.setSelectionRange(3, 3)
    editor.handleTextKeydown({ key: 'ArrowLeft', target: input, preventDefault })
    assert.deepEqual([input.selectionStart, input.selectionEnd], [0, 1])
    editor.handleTextKeydown({ key: 'ArrowRight', target: input, preventDefault })
    assert.deepEqual([input.selectionStart, input.selectionEnd], [3, 5])

    input.setSelectionRange(0, input.value.length)
    editor.handleTextBeforeInput({ inputType: 'deleteContentBackward', cancelable: true, target: input, preventDefault })
    assert.equal(input.value, 'м с')
    assert.equal(editor.state, null)

    editor.handleTextPaste({
        target: input,
        clipboardData: { getData: () => '3м 10с' },
        preventDefault,
    })
    assert.equal(input.value, '3м 10с')
    assert.equal(editor.state, 190)
})

test('text input recalculates enabled units when it loses focus', () => {
    const editor = tempoDuration({ state: 0, labels: {}, enabledUnits: ['hour', 'minute', 'second'] })
    editor.syncState(editor.state)
    editor.showText()
    editor.updateTextPart(editor.units[2], '90')
    editor.updateTextPart(editor.units[3], '125')

    assert.equal(editor.textValue(), '0h 90m 125s')
    assert.equal(editor.state, 5525)

    const input = { value: editor.textValue() }
    editor.normalizeTextOnBlur({ target: input })

    assert.equal(input.value, '1h 32m 5s')
    assert.equal(editor.state, 5525)

    const minutesOnly = tempoDuration({ state: 5400, labels: {}, enabledUnits: ['minute', 'second'] })
    minutesOnly.syncState(minutesOnly.state)
    minutesOnly.showText()
    minutesOnly.normalizeTextOnBlur({ target: { value: '' } })
    assert.equal(minutesOnly.textValue(), '90m 0s')
})

test('the smallest enabled unit controls representable seconds', () => {
    const editor = tempoDuration({ state: 90, labels: {}, enabledUnits: ['minute'] })
    editor.syncState(editor.state)

    assert.equal(editor.mode, 'text')
    assert.equal(editor.canShowVisual(), false)
    assert.equal(formatDuration(0, ['minute']), '0m')
    assert.equal(formatDuration(120, ['minute']), '2m')
    assert.equal(formatDuration(90, ['minute']), '')
})

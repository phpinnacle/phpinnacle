import assert from 'node:assert/strict'
import {readFileSync} from 'node:fs'
import test from 'node:test'
import paletteColorPicker from '../../resources/js/color-picker.js'

const colorPickerStyles = readFileSync(
    new URL('../../resources/css/color-picker.css', import.meta.url),
    'utf8',
)

test('previews and selects custom colors', () => {
    const picker = paletteColorPicker({state: '#2563eb'})

    picker.init()

    assert.equal(picker.customColor, '#2563eb')
    assert.equal(picker.previewColor(), '#2563eb')

    picker.customColor = '#dc2626'
    picker.selectCustomColor()

    assert.equal(picker.state, '#dc2626')
})

test('previews Filament semantic colors by their alias', () => {
    const picker = paletteColorPicker({
        state: 'primary',
        themeColors: {
            primary: '#2563eb',
        },
    })

    assert.equal(picker.previewColor(), '#2563eb')

    picker.selectColor('danger')

    assert.equal(picker.state, 'danger')
})

test('preserves opacity while selecting an RGBA color', () => {
    const picker = paletteColorPicker({
        state: '#ff000080',
        format: 'rgba',
    })

    picker.init()
    picker.customColor = '#2563eb'
    picker.selectCustomColor()

    assert.equal(picker.customColor, '#2563eb')
    assert.equal(picker.state, '#2563eb80')
})

test('syncs a wide popover with the input width', () => {
    const picker = paletteColorPicker({wide: true})
    let property
    let value

    picker.$root = {
        offsetWidth: 360,
        style: {
            setProperty(nextProperty, nextValue) {
                property = nextProperty
                value = nextValue
            },
        },
    }

    picker.syncPopoverWidth()

    assert.equal(property, '--phpinnacle-palette-color-picker-width')
    assert.equal(value, '360px')
})

test('keeps the regular popover compact', () => {
    assert.match(
        colorPickerStyles,
        /min-width: min\(12rem, calc\(100vw - 2rem\)\)/,
    )
    assert.match(
        colorPickerStyles,
        /\.phpinnacle-palette-color-picker__custom-input \{\s+width: 0;/,
    )
    assert.match(
        colorPickerStyles,
        /\.phpinnacle-palette-color-picker__theme \{[\s\S]*?gap: 0\.25rem;/,
    )
})

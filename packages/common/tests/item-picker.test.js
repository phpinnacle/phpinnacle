import assert from 'node:assert/strict'
import test from 'node:test'
import { itemPicker } from '../resources/js/item-picker.js'

function picker(options = {}) {
    const nodes = [
        { key: 'group', value: null, group: true, selectable: false, ancestors: [] },
        { key: 'root', value: 'products', branch: true, selectable: true, ancestors: [], parent: null },
        { key: 'child', value: 'food', branch: true, selectable: true, ancestors: ['root'], parent: 'root' },
        { key: 'leaf', value: 'dry', selectable: true, ancestors: ['root', 'child'], parent: 'child' },
        { key: 'disabled', value: 'archive', disabled: true, selectable: false, ancestors: [], parent: null },
        { key: 'last', value: 'brands', selectable: true, ancestors: [], parent: null },
    ]
    const component = itemPicker({ nodes, state: null, ...options })
    component.focus = function (key) { this.active = key }
    return component
}

function press(component, key, active) {
    component.keydown({ key, preventDefault() {}, stopPropagation() {} }, active)
}

test('reveals ancestors of a selected descendant and keeps groups out of navigation', () => {
    const component = picker({ state: 'dry' })
    component.revealSelection()
    assert.deepEqual(component.expanded, ['root', 'child'])
    assert.equal(component.active, 'leaf')
    assert.deepEqual(component.navigable().map(node => node.key), ['root', 'child', 'leaf', 'disabled', 'last'])
    assert.equal(component.tabIndex('leaf'), 0)
    assert.equal(component.tabIndex('root'), -1)
})

test('makes the first item keyboard reachable when optional selection is empty', () => {
    const component = picker()
    component.revealSelection()
    assert.equal(component.active, 'root')
    assert.equal(component.tabIndex('root'), 0)
    assert.equal(component.tabIndex('last'), -1)
    component.active = 'removed'
    component.state = 'unavailable'
    component.revealSelection()
    assert.equal(component.active, 'root')
})

test('expands and collapses a branch independently of the selected value', () => {
    const component = picker({ state: 'brands' })
    component.toggleBranch('root')
    assert.equal(component.visible(component.nodes[2]), true)
    assert.equal(component.visible(component.nodes[3]), false)
    assert.equal(component.state, 'brands')
    component.toggleBranch('root')
    assert.equal(component.visible(component.nodes[2]), false)
    assert.equal(component.tabIndex('root'), 0)
})

test('navigates visible tree items with arrows and returns to the parent', () => {
    const component = picker()
    press(component, 'ArrowDown', 'root')
    assert.equal(component.active, 'disabled')
    press(component, 'ArrowRight', 'root')
    assert.deepEqual(component.expanded, ['root'])
    press(component, 'ArrowRight', 'root')
    assert.equal(component.active, 'child')
    press(component, 'ArrowRight', 'child')
    press(component, 'ArrowDown', 'child')
    assert.equal(component.active, 'leaf')
    press(component, 'ArrowLeft', 'leaf')
    assert.equal(component.active, 'child')
    press(component, 'ArrowLeft', 'child')
    assert.deepEqual(component.expanded, ['root'])
    press(component, 'End', 'child')
    assert.equal(component.active, 'last')
    press(component, 'Home', 'last')
    assert.equal(component.active, 'root')
})

test('selects parents and descendants but rejects groups and disabled choices', () => {
    const component = picker()
    component.activate('root')
    assert.equal(component.state, 'products')
    component.activate('leaf')
    assert.equal(component.state, 'dry')
    component.activate('disabled')
    component.activate('group')
    assert.equal(component.state, 'dry')
    component.disabled = true
    component.activate('root')
    assert.equal(component.state, 'dry')
})

test('closes the select popup and returns focus after keyboard selection or Escape', () => {
    const component = picker({ select: true })
    let closed = 0
    let focused = 0
    component.$refs = { panel: { hidePopover() { closed++ } }, trigger: { focus() { focused++ } } }
    component.open = true
    press(component, 'Enter', 'root')
    assert.equal(component.state, 'products')
    assert.equal(component.open, false)
    component.open = true
    press(component, 'Escape', 'last')
    assert.equal(component.state, 'products')
    assert.equal(closed, 2)
    assert.equal(focused, 2)
})

test('keeps nonselectable parents expandable while selecting only leaves', () => {
    const component = picker({ state: 'brands', select: true })
    component.nodes[1].selectable = false
    component.nodes[2].selectable = false
    component.open = true
    component.close = function () { this.open = false }

    component.activate('root')
    assert.equal(component.state, 'brands')
    assert.equal(component.open, true)
    press(component, 'ArrowRight', 'root')
    press(component, 'ArrowRight', 'child')
    press(component, ' ', 'child')
    press(component, 'Enter', 'child')
    assert.deepEqual(component.expanded, ['root', 'child'])
    assert.equal(component.state, 'brands')
    component.activate('leaf')
    assert.equal(component.state, 'dry')
    assert.equal(component.open, false)

    component.nodes[1].selectable = true
    component.activate('root')
    assert.equal(component.state, 'products')
})

test('toggles a nonselectable parent by activating its row without selecting or closing the popup', () => {
    const component = picker({ state: 'brands', select: true })
    component.nodes[1].selectable = false
    component.open = true

    component.activate('root')
    assert.deepEqual(component.expanded, ['root'])
    assert.equal(component.state, 'brands')
    assert.equal(component.open, true)
    component.activate('root')
    assert.deepEqual(component.expanded, [])
    assert.equal(component.state, 'brands')
})

test('does not activate disabled parent rows or rows in a disabled picker', () => {
    const component = picker({ state: 'brands' })
    component.nodes[1].selectable = false
    component.nodes[1].disabled = true
    component.activate('root')
    assert.deepEqual(component.expanded, [])
    assert.equal(component.state, 'brands')

    component.nodes[1].disabled = false
    component.disabled = true
    component.activate('root')
    assert.deepEqual(component.expanded, [])
    assert.equal(component.state, 'brands')
})

test('toggles multiple selections with mouse and keyboard without closing the popup', () => {
    const component = picker({ multiple: true, state: [], select: true })
    let closed = false
    component.close = () => { closed = true }
    component.activate('root')
    component.activate('leaf')
    assert.deepEqual(component.state, ['products', 'dry'])
    assert.equal(component.selected('products'), true)
    assert.equal(component.selected('brands'), false)
    component.activate('root')
    assert.deepEqual(component.state, ['dry'])
    component.revealSelection()
    press(component, 'Enter', 'leaf')
    assert.deepEqual(component.state, [])
    component.activate('disabled')
    component.activate('group')
    assert.deepEqual(component.state, [])
    assert.equal(closed, false)
})

test('reveals all selected multiple branches while preserving the active item', () => {
    const component = picker({ multiple: true, state: ['dry', 'brands'] })
    component.revealSelection()
    assert.deepEqual(component.expanded, ['root', 'child'])
    assert.equal(component.active, 'leaf')
    component.active = 'last'
    component.revealSelection()
    assert.equal(component.active, 'last')
})

test('accepts optional absent multiple state and expands parents when branch selection is forbidden', () => {
    const component = picker({ multiple: true, state: null })
    assert.equal(component.selected('products'), false)
    component.nodes[1].selectable = false
    component.activate('root')
    assert.deepEqual(component.expanded, ['root'])
    assert.equal(component.state, null)
    component.activate('leaf')
    assert.deepEqual(component.state, ['dry'])
    component.activate('leaf')
    assert.deepEqual(component.state, [])
})

test('keeps saved unavailable values readable while distinguishing known selections from empty state', () => {
    for (const [options, text, values] of [
        [{ state: null }, null, []],
        [{ state: 'products' }, 'products', ['products']],
        [{ state: 'missing' }, 'missing', []],
        [{ state: 'archive' }, 'archive', ['archive']],
        [{ multiple: true, state: null }, '', []],
        [{ multiple: true, state: ['dry', 'missing'] }, 'dry, missing', ['dry']],
    ]) {
        const component = picker(options)
        assert.equal(component.selectionText(), text)
        assert.deepEqual(component.selectedNodes().map(node => node.value), values)
    }
})

test('fits complete selected chips and reserves space for the overflow count as the field resizes', () => {
    const component = picker({ multiple: true, select: true, state: ['products', 'food', 'brands'] })
    const items = [
        ['products', 'Products', 90],
        ['food', 'Food', 80],
        ['archive', 'Archive', 200],
        ['brands', 'Brands', 100],
    ].map(([value, label, width]) => ({
        dataset: { selectedValue: value },
        textContent: label,
        getBoundingClientRect: () => ({ width }),
    }))
    const container = { clientWidth: 250, querySelectorAll: () => items }
    const counter = { textContent: '', getBoundingClientRect: () => ({ width: counter.textContent.length * 8 }) }
    component.$refs = { value: container, overflow: counter }
    const originalGetComputedStyle = globalThis.getComputedStyle
    globalThis.getComputedStyle = () => ({ columnGap: '8px' })

    try {
        component.fitSelection()
        assert.deepEqual(component.visibleValues, ['products', 'food'])
        assert.equal(component.overflowCount, 1)
        assert.deepEqual(component.overflowLabels, ['Brands'])
        assert.equal(counter.textContent, '+1')
        assert.deepEqual(component.state, ['products', 'food', 'brands'])

        container.clientWidth = 195
        component.fitSelection()
        assert.deepEqual(component.visibleValues, ['products'])
        assert.equal(component.overflowCount, 2)

        container.clientWidth = 80
        component.fitSelection()
        component.fitSelection()
        assert.deepEqual(component.visibleValues, [])
        assert.equal(component.overflowCount, 3)
        assert.equal(counter.textContent, '+3')

        container.clientWidth = 286
        component.fitSelection()
        assert.deepEqual(component.visibleValues, ['products', 'food', 'brands'])
        assert.equal(component.overflowCount, 0)

        component.state = []
        component.fitSelection()
        assert.deepEqual(component.visibleValues, [])
        assert.equal(component.overflowCount, 0)
        assert.deepEqual(component.overflowLabels, [])
    } finally {
        globalThis.getComputedStyle = originalGetComputedStyle
    }
})

test('accounts for a wider overflow counter when ten or more selected chips are hidden', () => {
    const values = Array.from({ length: 12 }, (_, index) => `item-${index}`)
    const component = picker({ multiple: true, select: true, state: values })
    const items = values.map(value => ({
        dataset: { selectedValue: value },
        textContent: value,
        getBoundingClientRect: () => ({ width: 20 }),
    }))
    const counter = { textContent: '', getBoundingClientRect: () => ({ width: counter.textContent.length * 8 }) }
    component.$refs = { value: { clientWidth: 74, querySelectorAll: () => items }, overflow: counter }
    const originalGetComputedStyle = globalThis.getComputedStyle
    globalThis.getComputedStyle = () => ({ columnGap: '8px' })

    try {
        component.fitSelection()
        assert.deepEqual(component.visibleValues, ['item-0'])
        assert.equal(component.overflowCount, 11)
        assert.equal(counter.textContent, '+11')
    } finally {
        globalThis.getComputedStyle = originalGetComputedStyle
    }
})

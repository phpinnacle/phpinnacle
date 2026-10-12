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

function remotePicker(options = {}) {
    const component = picker({
        nodes: [],
        limit: 2,
        html: 'initial',
        selectionHtml: 'saved',
        selectionNodes: [{ key: 'saved', value: 'saved', selectable: true, ancestors: [] }],
        hasResolver: true,
        state: 'saved',
        ...options,
    })
    component.$nextTick = callback => callback()
    component.$refs = { panel: { scrollHeight: 500, scrollTop: 400, clientHeight: 100, querySelector: () => null } }
    return component
}

function portion(values, options = {}) {
    return {
        nodes: values.map(value => ({ key: value, value, selectable: true, ancestors: [] })),
        html: values.join(','),
        selectionNodes: [{ key: 'saved', value: 'saved', selectable: true, ancestors: [] }],
        selectionHtml: 'saved',
        empty: values.length === 0,
        ...options,
    }
}

test('appends scroll portions by the configured limit and stops on an empty result', async () => {
    const calls = []
    const component = remotePicker({ fetchItems: async (search, offset, state) => {
        calls.push([search, offset, state])
        return portion(offset === 0 ? ['one', 'two'] : offset === 2 ? ['three'] : [])
    } })
    await component.load(0)
    assert.deepEqual(component.nodes.map(node => node.value), ['one', 'two'])
    assert.equal(component.nextOffset, 2)
    assert.equal(component.selectionHtml, 'saved')
    await component.load(2)
    assert.equal(component.itemsHtml, 'one,twothree')
    assert.equal(component.nextOffset, 4)
    assert.equal(component.hasMore, true)
    await component.load(4)
    assert.equal(component.hasMore, false)
    component.loadMore()
    assert.deepEqual(calls, [[null, 0, 'saved'], [null, 2, 'saved'], [null, 4, 'saved']])
    assert.equal(component.selectedNodes()[0].value, 'saved')
    assert.equal(component.active, 'saved')
})

test('prevents duplicate scroll requests and retries a failed portion at the same offset', async () => {
    let resolve
    let count = 0
    const component = remotePicker({ fetchItems: () => {
        count++
        return new Promise(done => { resolve = done })
    } })
    const loading = component.load(2)
    component.loadMore()
    assert.equal(count, 1)
    resolve(portion(['three']))
    await loading
    component.fetchItems = async () => { throw new Error('Offline') }
    await component.load(4)
    assert.equal(component.error, true)
    assert.equal(component.errorOffset, 4)
    assert.equal(component.nextOffset, 4)
    component.loadMore()
    component.fetchItems = async (_, offset) => {
        assert.equal(offset, 4)
        return portion(['five'])
    }
    await component.load(component.errorOffset)
    assert.equal(component.error, false)
    assert.equal(component.nextOffset, 6)
})

test('debounces search, resets pagination, and ignores a response from the preceding search', async () => {
    let resolveOld
    const calls = []
    const component = remotePicker({ fetchItems: (search, offset) => {
        calls.push([search, offset])
        if (search === null) return new Promise(done => { resolveOld = done })
        return Promise.resolve(portion([search]))
    } })
    const old = component.load(2)
    component.search = 'a'
    component.scheduleSearch()
    component.search = 'anna'
    component.scheduleSearch()
    assert.equal(component.nodes.length, 0)
    assert.equal(component.hasMore, false)
    resolveOld(portion(['old']))
    await old
    assert.equal(component.nodes.length, 0)
    await new Promise(done => setTimeout(done, 350))
    assert.deepEqual(calls, [[null, 2], ['anna', 0]])
    assert.deepEqual(component.nodes.map(node => node.value), ['anna'])
    assert.equal(component.nextOffset, 2)
    assert.equal(component.selectionHtml, 'saved')
    component.destroy()
})

test('keeps selected items separate and ignores stale selection resolutions', async () => {
    let resolveOld
    const component = remotePicker({ fetchSelection: state => {
        if (state === 'saved') return new Promise(done => { resolveOld = done })
        return Promise.resolve({ nodes: [{ key: state, value: state, ancestors: [] }], html: state })
    } })
    const old = component.refreshSelection()
    component.state = 'new'
    await component.refreshSelection()
    resolveOld({ nodes: [{ key: 'saved', value: 'saved', ancestors: [] }], html: 'saved' })
    await old
    assert.equal(component.selectionHtml, 'new')
    assert.deepEqual(component.selectedNodes().map(node => node.value), ['new'])
    assert.equal(component.itemsHtml, 'initial')

    component.fetchSelection = async () => { throw new Error('Offline') }
    await component.refreshSelection()
    assert.equal(component.selectionError, true)
})

test('does not replace a newer selection with a search response carrying an older state', async () => {
    let resolve
    const component = remotePicker({ fetchItems: () => new Promise(done => { resolve = done }) })
    const loading = component.load(0)
    component.state = 'new'
    component.selectionHtml = 'new'
    resolve(portion(['result']))
    await loading
    assert.equal(component.selectionHtml, 'new')
    assert.deepEqual(component.nodes.map(node => node.value), ['result'])
})

test('unlimited and disabled pickers do not load scroll portions', () => {
    for (const options of [{ limit: null }, { disabled: true }]) {
        let called = false
        const component = remotePicker({ ...options, fetchItems: () => { called = true } })
        component.loadMore()
        assert.equal(called, false)
    }
})

test('keeps resolved inline cards in the current results when selection changes or later portions contain them', async () => {
    const component = remotePicker({ fetchItems: async (_, offset) => portion(offset === 0 ? ['other'] : ['saved']) })
    await component.load(0)
    component.activate('other')
    assert.equal(component.selected('saved'), false)
    assert.equal(component.inlineVisible('saved'), true)
    component.fetchSelection = async () => ({ nodes: [{ key: 'selected-other', value: 'other', ancestors: [] }], html: 'other' })
    await component.refreshSelection()
    assert.equal(component.inlineVisible('saved'), true)
    assert.equal(component.inlineHtml, 'saved')
    await component.load(2)
    assert.equal(component.inlineVisible('saved'), true)
    assert.equal(component.navigable().filter(node => node.value === 'saved').length, 1)
    assert.equal(component.nextOffset, 4)
    component.activate(component.inlineNodes[0].key)
    assert.equal(component.state, 'saved')

    component.search = 'different'
    component.fetchItems = async () => portion(['different-search-result'])
    await component.load(0)
    assert.deepEqual(component.inlineNodes.map(node => node.value), ['saved'])
    component.state = 'different-search-result'
    component.fetchItems = async () => portion(['different-search-result'], {
        selectionNodes: [{ key: 'selected-result', value: 'different-search-result', ancestors: [] }],
        selectionHtml: 'different-search-result',
    })
    component.search = ''
    await component.load(0)
    assert.equal(component.inlineVisible('saved'), false)
    assert.equal(component.inlineNodes.length, 0)
})

test('retains deselected multiple inline cards until the search changes and keeps dropdown selections independent', () => {
    const component = remotePicker({
        multiple: true,
        state: ['loaded', 'missing'],
        nodes: [{ key: 'loaded-row', value: 'loaded', ancestors: [] }],
        selectionNodes: [
            { key: 'selected-loaded', value: 'loaded', ancestors: [] },
            { key: 'selected-missing', value: 'missing', selectable: true, ancestors: [] },
        ],
    })
    assert.equal(component.inlineVisible('loaded'), false)
    assert.equal(component.inlineVisible('missing'), true)
    component.activate('selected-missing')
    assert.deepEqual(component.state, ['loaded'])
    assert.equal(component.selected('missing'), false)
    assert.equal(component.inlineVisible('missing'), true)
    component.activate('selected-missing')
    assert.deepEqual(component.state, ['loaded', 'missing'])
    component.select = true
    assert.equal(component.inlineVisible('missing'), false)
    assert.equal(component.selected('loaded'), true)
    assert.equal(component.selected('missing'), true)
    component.state = ['loaded']
    assert.equal(component.selected('missing'), false)
})

test('keeps loaded portions and retained inline cards when Livewire only updates selection', async () => {
    let fetched = 0
    const component = remotePicker({ fetchItems: async () => {
        fetched++
        return portion(['three'])
    } })
    const initial = { ...portion(['one', 'two']), limit: 2, hasResolver: true, searchable: true, disabled: false }
    component.$refs.panel.scrollTop = 0
    component.sync(initial)
    await component.load(2)
    component.state = 'one'
    component.sync({
        ...initial,
        selectionNodes: [{ key: 'selected-one', value: 'one', ancestors: [] }],
        selectionHtml: 'one',
    })
    assert.equal(fetched, 1)
    assert.deepEqual(component.nodes.map(node => node.value), ['one', 'two', 'three'])
    assert.equal(component.nextOffset, 4)
    assert.equal(component.inlineHtml, 'saved')
    assert.equal(component.selectionHtml, 'one')
})

test('uses the full tree row when a retained selection loads as a branch', async () => {
    const component = remotePicker({ fetchItems: async () => portion(['saved', 'child'], {
        nodes: [
            { key: 'loaded-parent', value: 'saved', branch: true, selectable: true, ancestors: [] },
            { key: 'loaded-child', value: 'child', selectable: true, ancestors: ['loaded-parent'], parent: 'loaded-parent' },
        ],
    }) })
    await component.load(2)
    assert.equal(component.inlineVisible('saved'), false)
    assert.deepEqual(component.navigable().map(node => node.key), ['loaded-parent'])
    component.toggleBranch('loaded-parent')
    assert.deepEqual(component.navigable().map(node => node.key), ['loaded-parent', 'loaded-child'])
    component.activate('loaded-child')
    assert.equal(component.state, 'child')
})

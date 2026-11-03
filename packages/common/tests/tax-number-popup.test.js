import assert from 'node:assert/strict'
import test from 'node:test'
import taxNumberPopup from '../resources/js/tax-number-popup.js'

const found = { message: null, lookup: { name: 'Example', status: 'Active', address: null } }
const popup = lookup => taxNumberPopup({ lookup, unavailable: 'Unavailable' })

test('blocks repeated requests while loading and displays the returned information', async () => {
    let finish
    let calls = 0
    const component = popup(() => {
        calls++
        return new Promise(resolve => { finish = resolve })
    })

    const pending = component.load()
    assert.equal(component.loading, true)
    await component.load()
    assert.equal(calls, 1)
    finish(found)
    await pending
    assert.equal(component.loading, false)
    assert.deepEqual(component.lookup, found.lookup)
    assert.equal(component.message, null)
})

test('clears previous information before refreshing it', async () => {
    let finish
    const component = popup(() => new Promise(resolve => { finish = resolve }))
    component.lookup = found.lookup

    const pending = component.load()
    assert.equal(component.lookup, null)
    finish(found)
    await pending

    assert.deepEqual(component.lookup, found.lookup)
    assert.equal(component.loading, false)
})

test('shows missing records separately from transport failures and permits retrying', async () => {
    let attempts = 0
    const component = popup(() => {
        if (++attempts === 1) return Promise.resolve({ message: 'Not found', lookup: null })
        return Promise.reject(new Error('Network failure'))
    })

    await component.load()
    assert.equal(component.message, 'Not found')
    await component.load()
    assert.equal(component.message, 'Unavailable')
    assert.equal(component.loading, false)
    assert.equal(component.lookup, null)
})

test('keeps instances independent and discards responses after removal', async () => {
    let finish
    const first = popup(() => new Promise(resolve => { finish = resolve }))
    const second = popup(() => Promise.resolve(found))

    const pending = first.load()
    await second.load()
    first.destroy()
    finish(found)
    await pending

    assert.equal(first.lookup, null)
    assert.deepEqual(second.lookup, found.lookup)
})

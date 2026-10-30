import assert from 'node:assert/strict';
import { test } from 'node:test';
import fieldBinding from '../resources/js/field-binding.js';

function editor(disabled = false, setTarget = () => {}) {
    return fieldBinding({
        disabled,
        sourceKeys: ['name', 'email'],
        setTarget,
        state: [
            { kind: 'source', source: 'name', target: { destination: 'customer.name' }, options: { transforms: [] } },
            { kind: 'source', source: 'email', target: null, options: {} },
            { kind: 'static', target: 'status', static: 'active', options: {} },
        ],
    });
}

test('opening a native editor preserves null until a value is entered', () => {
    const component = editor();
    const state = structuredClone(component.state);
    component.bind('email', null);
    assert.equal(component.isEditing('email'), true);
    assert.equal(component.isBound('email'), false);
    assert.equal(component.boundCount(), 1);
    assert.deepEqual(component.state, state);
    component.state[1].target = 'contact.email';
    assert.equal(component.boundCount(), 2);
    component.unbind('email');
    assert.equal(component.isEditing('email'), false);
    assert.deepEqual(component.state, state);
});

test('unbind resets only the native target and keeps settings and static rows', () => {
    const component = editor();
    const settings = component.state[0].options;
    const constant = structuredClone(component.state[2]);
    component.unbind('name');
    component.unbind('name');
    assert.equal(component.state[0].target, null);
    assert.equal(component.state[0].options, settings);
    assert.deepEqual(component.state[2], constant);
});

for (const target of [false, 0, '', [], {}]) {
    test(`native ${JSON.stringify(target)} is a bound value`, () => {
        const component = editor();
        component.state[1].target = target;
        assert.equal(component.isBound('email'), true);
        assert.equal(component.boundCount(), 2);
    });
}

test('disabled bindings cannot open editors or unbind', () => {
    const component = editor(true, () => assert.fail('disabled fields must not update Livewire state'));
    const original = structuredClone(component.state);
    component.bind('email', null);
    component.unbind('name');
    assert.equal(component.isEditing('email'), false);
    assert.deepEqual(component.state, original);
});

test('filters combine with search without changing native rows', () => {
    const component = editor();
    const state = structuredClone(component.state);
    for (const [filter, boundVisible, unboundVisible] of [
        ['all', true, true], ['bound', true, false], ['unbound', false, true],
    ]) {
        component.visibilityFilter = filter;
        assert.equal(component.isVisible('name', 'Customer name'), boundVisible);
        assert.equal(component.isVisible('email', 'Email address'), unboundVisible);
        assert.equal(component.matchesFilter(true), boundVisible);
        component.search = ' EMAIL ';
        assert.equal(component.isVisible('name', 'Customer name'), false);
        assert.equal(component.isVisible('email', 'Email address'), unboundVisible);
        component.search = '';
    }
    assert.deepEqual(component.state, state);
});

test('connection visibility follows the native target after editing', () => {
    const component = editor();
    component.visibilityFilter = 'unbound';
    component.bind('email', null);
    assert.equal(component.isVisible('email', 'Email'), true);
    component.state[1].target = 'contact.email';
    assert.equal(component.isVisible('email', 'Email'), false);
    component.visibilityFilter = 'bound';
    assert.equal(component.isVisible('email', 'Email'), true);
    component.unbind('email');
    assert.equal(component.isVisible('email', 'Email'), false);
});

for (const defaultValue of ['email', false, 0, [], { destination: 'contact.email', rules: [{ trim: true }] }]) {
    test(`local binding preserves typed defaults and existing values: ${JSON.stringify(defaultValue)}`, () => {
        const component = editor();
        const settings = component.state[1].options;
        const expected = structuredClone(defaultValue);
        component.bind('email', defaultValue);
        assert.deepEqual(component.state[1].target, expected);
        assert.equal(component.isEditing('email'), true);
        assert.equal(component.isBound('email'), true);
        component.state[1].target = 'edited';
        component.bind('email', defaultValue);
        assert.equal(component.state[1].target, 'edited');
        component.unbind('email');
        assert.equal(component.isEditing('email'), false);
        assert.equal(component.state[1].target, null);
        component.bind('email', defaultValue);
        assert.deepEqual(component.state[1].target, expected);
        assert.equal(component.state[1].options, settings);
        assert.deepEqual(defaultValue, expected);
    });
}

test('binding calls setTarget with its row index before assigning the local target', () => {
    const calls = [];
    const component = editor(false, (index, value) => {
        calls.push([index, structuredClone(value)]);
        assert.equal(component.state[index].target, null);
    });
    component.bind('email', { destination: 'contact.email', enabled: true });
    assert.deepEqual(calls, [[1, { destination: 'contact.email', enabled: true }]]);
});

for (const [type, defaultValue, edit] of [
    ['array', ['trim'], value => value.push('lowercase')],
    ['object', { rules: [{ trim: true }] }, value => { value.rules[0].trim = false; }],
]) {
    test(`binding restores an independent ${type} default after nested edits`, () => {
        const expected = structuredClone(defaultValue);
        const first = editor();
        const second = editor();
        first.bind('email', defaultValue);
        second.bind('email', defaultValue);

        for (let cycle = 0; cycle < 2; cycle++) {
            edit(first.state[1].target);
            first.bind('email', defaultValue);
            assert.notDeepEqual(first.state[1].target, expected);
            assert.deepEqual(second.state[1].target, expected);
            assert.deepEqual(defaultValue, expected);
            first.unbind('email');
            first.bind('email', defaultValue);
            assert.deepEqual(first.state[1].target, expected);
        }
    });
}

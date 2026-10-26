import assert from 'node:assert/strict';
import { test } from 'node:test';
import fieldBinding from '../resources/js/field-binding.js';

function editor(disabled = false) {
    return fieldBinding({
        disabled,
        sourceKeys: ['name', 'email'],
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
    component.edit('email');
    assert.equal(component.isEditing('email'), true);
    assert.equal(component.isBound('email'), false);
    assert.equal(component.boundCount(), 1);
    assert.deepEqual(component.state, state);
    component.state[1].target = 'contact.email';
    assert.equal(component.boundCount(), 2);
    component.unbind('email');
    component.closeEditor('email');
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
    const component = editor(true);
    const original = structuredClone(component.state);
    component.edit('email');
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
    component.edit('email');
    assert.equal(component.isVisible('email', 'Email'), true);
    component.state[1].target = 'contact.email';
    assert.equal(component.isVisible('email', 'Email'), false);
    component.visibilityFilter = 'bound';
    assert.equal(component.isVisible('email', 'Email'), true);
    component.unbind('email');
    assert.equal(component.isVisible('email', 'Email'), false);
});

import assert from 'node:assert/strict';
import { test } from 'node:test';
import tabbedBuilder from '../resources/js/tabbed-builder.js';

function editor(keys = [], options = {}) {
    const calls = [];
    const component = tabbedBuilder({ context: { schemaComponent: 'form.content' }, reorderAction: 'reorder', reorderable: true, vertical: false, ...options });
    component.$refs = { metadata: { dataset: { keys: JSON.stringify(keys) } }, tabs: { children: keys.map(key => ({ getAttribute: () => key })) } };
    component.$nextTick = callback => callback();
    component.$wire = { mountAction: (...args) => calls.push(args) };
    component.sync();
    return { component, calls, sync(keys) {
        component.$refs.metadata.dataset.keys = JSON.stringify(keys);
        component.sync();
    } };
}

test('selects the first item initially and each newly added item including after empty state', () => {
    const { component, sync } = editor();
    assert.equal(component.active, null);
    sync(['a']);
    assert.equal(component.active, 'a');
    sync(['a', 'b']);
    assert.equal(component.active, 'b');
});

test('reordering preserves the selected instance and removing it chooses the next or previous', () => {
    const { component, sync } = editor(['a', 'b', 'c']);
    component.select('b');
    sync(['c', 'b', 'a']);
    assert.equal(component.active, 'b');
    sync(['c', 'a']);
    assert.equal(component.active, 'a');
    sync(['c']);
    assert.equal(component.active, 'c');
    sync([]);
    assert.equal(component.active, null);
});

test('drag and keyboard moves send full instance permutations to the owning action', () => {
    const { component, calls } = editor(['b', 'a', 'c']);
    component.reorder({ oldDraggableIndex: 0, newDraggableIndex: 1 });
    component.move('a', -1);
    component.move('b', -1);
    component.reorder({ oldDraggableIndex: 0, newDraggableIndex: 0 });
    assert.deepEqual(calls, [
        ['reorder', { items: ['b', 'a', 'c'] }, { schemaComponent: 'form.content' }],
        ['reorder', { items: ['a', 'b', 'c'] }, { schemaComponent: 'form.content' }],
    ]);
});

test('disabled ordering sends no requests and vertical keyboard moves use up and down', () => {
    const { component, calls } = editor(['a', 'b'], { reorderable: false });
    component.move('a', 1);
    component.reorder({ oldDraggableIndex: 0, newDraggableIndex: 1 });
    assert.deepEqual(calls, []);
    const vertical = editor(['a', 'b'], { vertical: true });
    vertical.component.keydown({ key: 'ArrowDown', altKey: true, preventDefault() {} }, 'a');
    assert.deepEqual(vertical.calls[0][1], { items: ['b', 'a'] });
});

test('tab navigation wraps and supports Home and End without a server request', () => {
    const { component, calls } = editor(['a', 'b']);
    const focused = [];
    component.$refs.tabs.querySelectorAll = () => ['a', 'b'].map(key => ({ focus: () => focused.push(key), scrollIntoView() {} }));
    component.keydown({ key: 'ArrowLeft', preventDefault() {} }, 'a');
    assert.equal(component.active, 'b');
    component.keydown({ key: 'Home', preventDefault() {} }, 'b');
    assert.equal(component.active, 'a');
    component.keydown({ key: 'End', preventDefault() {} }, 'a');
    assert.equal(component.active, 'b');
    assert.deepEqual(focused, ['b', 'a', 'b']);
    assert.deepEqual(calls, []);
});

import assert from 'node:assert/strict';
import { test } from 'node:test';
import fieldMapping from '../resources/js/field-mapping.js';

function sourceEntry(source) { return { kind: 'source', source }; }
function value(value) { return { kind: 'value', value }; }

const scalarModes = { title: { name: 'whole', contact: 'whole' }, email: { name: 'whole', contact: 'whole' } };
const itemModes = { title: { name: 'item', contact: 'whole' }, email: { name: 'item', contact: 'whole' } };

function editor(state = {}, disabled = false, options = {}) {
    const ports = [
        ['source', 'title', 100, 80],
        ['source', 'email', 100, 160],
        ['target', 'name', 400, 80],
        ['target', 'contact', 400, 160],
    ].map(([side, id, left, top]) => ({
        dataset: { side, id, label: id },
        getBoundingClientRect: () => ({ left: options.inverse ? 500 - left : left, top, width: 16, height: 16 }),
        querySelector() { return this; },
    }));
    const connectionModes = structuredClone(options.connectionModes ?? scalarModes);
    const element = { querySelectorAll: (selector) => selector === '[data-mapping-port]' ? ports : [] };
    const rows = [];
    for (const [target, mapped] of Object.entries(state ?? {})) {
        for (const entry of Array.isArray(mapped) ? mapped : [mapped]) {
            rows.push(entry.kind === 'value'
                ? { kind: 'static', target, static: entry.value, options: {} }
                : { kind: 'source', source: entry.source, target, options: structuredClone(options.sourceSettings?.[entry.source] ?? {}) });
        }
    }
    for (const source of ['title', 'email', ...Object.keys(options.sourceSettings ?? {})]) {
        if (!rows.some(row => row.kind === 'source' && row.source === source)) rows.push({ kind: 'source', source, target: null, options: structuredClone(options.sourceSettings?.[source] ?? {}) });
    }
    const targetModes = options.targetModes ?? Object.assign({ name: 'whole', contact: 'whole' }, ...Object.values(connectionModes));
    const component = fieldMapping({ element, state: rows, disabled, connectionModes, targetModes, requiredTargets: options.requiredTargets, inverse: options.inverse, staticDefaults: options.staticDefaults });
    component.$root = element;
    component.$refs = { canvas: { getBoundingClientRect: () => ({ left: 20, top: 40, width: 500, height: 300 }) } };
    component.$nextTick = (callback) => callback();
    return component;
}

test('partial configuration updates refresh compatibility and ports while keeping drafts and filters', () => {
    const component = editor({ name: sourceEntry('title') }, false, { sourceSettings: { title: { transforms: [{ type: 'trim' }] } } });
    component.search = 'title';
    component.visibilityFilter = 'mapped';
    component.refresh();
    const state = structuredClone(component.state);
    const ports = component.$root.querySelectorAll('[data-mapping-port]');
    component.$root = { querySelectorAll: () => [] };
    ports.push({ ...ports[0], dataset: { side: 'source', id: 'static_1', label: 'Static 1' } });
    component.selected = { side: 'source', id: 'title' };
    component.updateConfiguration({ title: { contact: 'whole' }, static_1: { name: 'whole' } }, ['title', 'email', 'static_1'], { title: 'boolean' }, { name: 'whole', contact: 'whole' });
    assert.equal(component.hasTypeConflict('name'), true);
    assert.equal(component.canConnect('static_1', 'name'), true);
    assert.equal(component.search, 'title');
    assert.equal(component.visibilityFilter, 'mapped');
    assert.deepEqual(component.selected, { side: 'source', id: 'title' });
    component.search = 'boolean';
    assert.equal(component.isVisible('source', 'title', 'Title'), true);
    component.search = 'string';
    assert.equal(component.isVisible('source', 'title', 'Title'), false);
    assert.deepEqual(component.state, state);
    component.selected = { side: 'source', id: 'static_1' };
    ports.pop();
    component.updateConfiguration(scalarModes, ['title', 'email'], { title: 'string' }, { name: 'whole', contact: 'whole' });
    assert.equal(component.selected, null);
    assert.equal(component.canConnect('static_1', 'name'), false);
    assert.equal(component.hasTypeConflict('name'), false);
    assert.equal(component.isVisible('source', 'title', 'Title'), true);
    assert.deepEqual(component.state, state);
});

test('native static editors switch modes locally and preserve typed defaults', () => {
    const component = editor({}, false, {
        staticDefaults: { name: false },
    });
    component.restoreStaticDefault('name');
    assert.deepEqual(component.mappings(), { name: value(false) });
    component.state.find(row => row.kind === 'static' && row.target === 'name').static = true;
    component.connect('title', 'name');
    assert.deepEqual(component.mappings(), { name: sourceEntry('title') });
    component.refresh();
    assert.deepEqual(component.connections.map(({ source, target }) => ({ source, target })), [{ source: 'title', target: 'name' }]);
    component.remove('name');
    assert.deepEqual(component.mappings(), { name: value(false) });
});

for (const [type, initial, edit] of [
    ['array', ['default'], value => value.push('edited')],
    ['object', { options: { enabled: true } }, value => { value.options.enabled = false; }],
]) {
    test(`repeated disconnects restore an independent ${type} default from reactive state`, () => {
        const expected = structuredClone(initial);
        const defaults = { name: new Proxy(initial, {}) };
        const component = editor({}, false, { staticDefaults: defaults });
        component.restoreStaticDefault('name');

        for (let cycle = 0; cycle < 2; cycle++) {
            edit(component.state.find(row => row.kind === 'static' && row.target === 'name').static);
            assert.deepEqual(defaults.name, expected);
            component.connect('title', 'name');
            component.remove('name');
            assert.deepEqual(component.mappings().name, value(expected));
        }
    });
}

for (const value of [null, '', false, 0, []]) {
    test(`removing a connection restores its static form default ${JSON.stringify(value)} as a mapped value`, () => {
        const component = editor({ name: sourceEntry('title') }, false, {
            staticDefaults: { name: value }, requiredTargets: ['name'],
        });
        component.remove('name', 'title');
        assert.deepEqual(component.mappings(), { name: { kind: 'value', value } });
        assert.equal(component.isMapped('target', 'name'), true);
        assert.equal(component.mappedTargetCount(), 1);
        assert.deepEqual(component.missingRequired(), []);
        component.refresh();
        assert.deepEqual(component.connections, []);
        assert.equal(component.isDisabled('source', 'title'), false);
    });
}

test('connects by selecting fields in either order and appends sources to a multiple destination', () => {
    const component = editor({}, false, { connectionModes: itemModes });
    component.fieldBindings('source', 'title')['@click']();
    component.refresh();
    assert.deepEqual(component.selected, { side: 'source', id: 'title' });
    assert.deepEqual(component.mappings(), {});
    assert.deepEqual(component.connections, []);
    component.select('target', 'name');
    component.select('target', 'name');
    component.select('source', 'email');
    assert.deepEqual(component.mappings(), { name: [sourceEntry('title'), sourceEntry('email')] });
    component.remove('name', 'email');
    component.select('source', 'email');
    component.select('target', 'contact');
    assert.deepEqual(component.mappings(), { name: [sourceEntry('title')], contact: sourceEntry('email') });
    assert.equal(component.selected, null);
});

test('selection marks compatible candidates and dims incompatible fields regardless of mappings', () => {
    const state = { name: sourceEntry('title') };
    const component = editor(state, false, {
        connectionModes: { title: { name: 'whole', contact: 'whole' }, email: {} },
    });

    for (const [side, id] of [['source', 'title'], ['target', 'name']]) {
        assert.equal(component.rowClasses(side, id, false)['is-mapped'], true);
        assert.equal(component.rowClasses(side, id, false)['is-incompatible'], false);
    }
    assert.equal(component.fieldBindings('source', 'title')[':disabled'](), true);

    component.select('target', 'contact');
    assert.equal(component.rowClasses('source', 'title', false)['is-incompatible'], true);
    assert.equal(component.rowClasses('source', 'email', false)['is-incompatible'], true);
    component.cancel();
    component.select('source', 'email');
    assert.equal(component.rowClasses('target', 'name', false)['is-incompatible'], true);
    assert.equal(component.rowClasses('target', 'contact', false)['is-incompatible'], true);
    assert.deepEqual(component.mappings(), state);
});

test('multiple permission applies only to listed destinations and still enforces types', () => {
    const component = editor({}, false, { connectionModes: itemModes });
    component.connect('title', 'name');
    component.connect('email', 'name');
    assert.deepEqual(component.mappings(), { name: [sourceEntry('title'), sourceEntry('email')] });
    assert.equal(component.isDisabled('source', 'title'), true);
    component.connect('title', 'contact');
    assert.deepEqual(component.mappings(), { name: [sourceEntry('title'), sourceEntry('email')] });
    component.connect('title', 'name');
    assert.deepEqual(component.mappings(), { name: [sourceEntry('title'), sourceEntry('email')] });
    component.remove('name');
    assert.equal(component.isDisabled('source', 'title'), false);
    component.connect('title', 'contact');
    component.connect('email', 'contact');
    assert.deepEqual(component.mappings(), { contact: sourceEntry('email') });

    delete component.connectionModes.title.name;
    component.connect('title', 'name');
    assert.deepEqual(component.mappings(), { contact: sourceEntry('email') });
});

test('field controls track source locks, selection, and read-only state after creation', () => {
    const component = editor({ name: sourceEntry('title') });
    const source = component.fieldBindings('source', 'title');
    const target = component.fieldBindings('target', 'contact');

    assert.equal(source[':disabled'](), true);
    assert.equal(source[':aria-disabled'](), true);
    assert.equal(target[':disabled'](), false);
    source['@click']();
    assert.equal(source[':aria-pressed'](), false);

    target['@click']();
    source['@click']();
    assert.deepEqual(component.selected, { side: 'target', id: 'contact' });
    assert.deepEqual(component.mappings(), { name: sourceEntry('title') });
    component.cancel();

    component.remove('name');
    assert.equal(source[':disabled'](), false);
    source['@click']();
    assert.equal(source[':aria-pressed'](), true);
    target['@click']();
    assert.deepEqual(component.mappings(), { contact: sourceEntry('title') });
    assert.equal(source[':disabled'](), true);
    assert.equal(source[':aria-pressed'](), false);

    component.disabled = true;
    assert.equal(target[':disabled'](), true);
    assert.equal(target[':aria-disabled'](), true);
});

for (const firstSide of ['source', 'target']) {
    test(`incompatible controls ignore clicks after selecting a ${firstSide}`, () => {
        const component = editor({}, false, {
            connectionModes: { title: { contact: 'whole' }, email: { name: 'whole', contact: 'whole' } },
        });
        const secondSide = firstSide === 'source' ? 'target' : 'source';
        const firstId = firstSide === 'source' ? 'title' : 'name';
        const secondId = secondSide === 'source' ? 'title' : 'name';
        component.select(firstSide, firstId);
        const announcement = component.announcement;
        const candidate = component.fieldBindings(secondSide, secondId);

        assert.equal(candidate[':aria-disabled'](), true);
        candidate['@click']();
        candidate['@click']();
        assert.deepEqual(component.mappings(), {});
        assert.deepEqual(component.selected, { side: firstSide, id: firstId });
        assert.equal(component.announcement, announcement);
        assert.equal(component.rowClasses('source', 'title', false)['has-type-conflict'], false);

        component.connectionModes.title.name = 'whole';
        candidate['@click']();
        assert.deepEqual(component.mappings(), { name: sourceEntry('title') });
        assert.equal(component.selected, null);
    });
}

test('separate editors keep their state and port geometry independent', () => {
    const first = editor({ name: sourceEntry('title') });
    const second = editor({ name: sourceEntry('title') });
    first.refresh();
    second.refresh();
    first.port('source', 'title').getBoundingClientRect = () => ({ left: 0, top: 0, width: 0, height: 0 });
    first.refresh();
    second.refresh();

    assert.equal(first.connections.length, 0);
    assert.equal(second.connections.length, 1);
    first.remove('name');
    assert.deepEqual(first.mappings(), {});
    assert.deepEqual(second.mappings(), { name: sourceEntry('title') });
});

test('read-only fields do not change state through selection, port clicks, or removal', () => {
    const component = editor({ name: sourceEntry('title'), contact: value(false) }, true);
    component.select('source', 'email');
    component.connect('email', 'name');
    component.remove('name');
    component.connect('email', 'contact');
    component.remove('contact');
    component.fieldBindings('source', 'email')['@click']();
    component.fieldBindings('target', 'name')['@click']();
    assert.deepEqual(component.mappings(), { name: sourceEntry('title'), contact: value(false) });
    assert.equal(component.selected, null);
});

test('all, mapped and unmapped filters combine with search and preserve state', () => {
    const state = { name: sourceEntry('title') };
    const component = editor(state);
    const rows = structuredClone(component.state);
    assert.equal(component.visibilityFilter, 'all');
    for (const side of ['source', 'target']) {
        const mapped = side === 'source' ? 'title' : 'name';
        const unmapped = side === 'source' ? 'email' : 'contact';
        for (const [filter, mappedVisible, unmappedVisible] of [
            ['all', true, true], ['mapped', true, false], ['unmapped', false, true],
        ]) {
            component.visibilityFilter = filter;
            assert.equal(component.isVisible(side, mapped, 'Mapped field'), mappedVisible);
            assert.equal(component.isVisible(side, unmapped, 'Other field'), unmappedVisible);
            component.search = ' MAPPED ';
            assert.equal(component.isVisible(side, mapped, 'Mapped field'), mappedVisible);
            assert.equal(component.isVisible(side, unmapped, 'Other field'), false);
            component.search = '';
        }
    }
    component.visibilityFilter = 'all';
    component.search = ' NAME ';
    assert.equal(component.isVisible('source', 'title', 'Display name'), true);
    assert.equal(component.isVisible('target', 'name', 'Customer name'), true);
    assert.equal(component.isVisible('source', 'email', 'Email address'), false);
    assert.equal(component.isVisible('target', 'contact', 'Contact email'), false);
    component.search = '';
    assert.equal(component.isVisible('source', 'email', 'Email address'), true);
    assert.equal(component.isVisible('target', 'contact', 'Contact email'), true);
    assert.deepEqual(component.mappings(), state);
    assert.deepEqual(component.state, rows);
});

for (const initial of [false, []]) {
    test(`visibility filters only established connections with static value ${JSON.stringify(initial)}`, () => {
        const component = editor({ name: value(initial) }, false, { staticDefaults: { name: initial } });
        const state = structuredClone(component.state);
        for (const [filter, visible] of [['all', true], ['mapped', false], ['unmapped', true]]) {
            component.visibilityFilter = filter;
            assert.equal(component.isVisible('target', 'name', 'Name'), visible);
            assert.equal(component.isVisible('source', 'title', 'Title'), visible);
        }
        assert.deepEqual(component.state, state);
        component.connect('title', 'name');
        assert.equal(component.isVisible('source', 'title', 'Title'), false);
        assert.equal(component.isVisible('target', 'name', 'Name'), false);
        component.visibilityFilter = 'mapped';
        assert.equal(component.isVisible('source', 'title', 'Title'), true);
        assert.equal(component.isVisible('target', 'name', 'Name'), true);
        component.remove('name');
        assert.equal(component.isVisible('source', 'title', 'Title'), false);
        assert.equal(component.isVisible('target', 'name', 'Name'), false);
        assert.deepEqual(component.mappings(), { name: value(initial) });
    });
}

test('line endpoints follow the actual ports and hidden ports do not produce lines', () => {
    const component = editor({ name: sourceEntry('title'), contact: sourceEntry('email') });
    component.refresh();
    assert.equal(component.connections.length, 2);
    assert.match(component.connections.find(({ source }) => source === 'title').path, /^M 88 48 .*388 48$/);
    component.port('target', 'name').getBoundingClientRect = () => ({ left: 0, top: 0, width: 0, height: 0 });
    component.refresh();
    assert.deepEqual(component.connections.map((connection) => connection.target), ['contact']);
    assert.deepEqual(component.mappings(), { name: sourceEntry('title'), contact: sourceEntry('email') });
});

test('line endpoints follow visible anchors when their centers differ from the port buttons', () => {
    const component = editor({ name: sourceEntry('title') });
    component.port('source', 'title').querySelector = () => ({
        getBoundingClientRect: () => ({ left: 101, top: 65, width: 14, height: 14 }),
    });
    component.port('target', 'name').querySelector = () => ({
        getBoundingClientRect: () => ({ left: 401, top: 65, width: 14, height: 14 }),
    });
    component.refresh();

    assert.match(component.connections[0].path, /^M 88 32 .*388 32$/);
    assert.deepEqual(component.connections[0].midpoint, { x: 238, y: 32 });
    component.port('target', 'name').querySelector = () => ({
        getBoundingClientRect: () => ({ left: 0, top: 0, width: 0, height: 0 }),
    });
    component.refresh();
    assert.deepEqual(component.connections, []);
});

test('unknown identifiers cannot replace existing connections', () => {
    const component = editor({ name: sourceEntry('title') });
    component.connect('unknown', 'name');
    component.connect('title', 'unknown');
    component.refresh();
    assert.deepEqual(component.mappings(), { name: sourceEntry('title') });
    assert.equal(component.connections.length, 1);
});

test('destination port clicks also connect and a repeated click cancels selection', () => {
    const component = editor();
    component.fieldBindings('target', 'name')['@click']();
    component.fieldBindings('source', 'title')['@click']();
    assert.deepEqual(component.mappings(), { name: sourceEntry('title') });
    component.fieldBindings('target', 'name')['@click']();
    assert.deepEqual(component.selected, { side: 'target', id: 'name' });
    component.fieldBindings('target', 'name')['@click']();
    assert.equal(component.selected, null);
    assert.deepEqual(component.mappings(), { name: sourceEntry('title') });
});

test('cancelling selection clears candidate highlighting without changing mappings', () => {
    const component = editor({}, false, {
        connectionModes: { title: { contact: 'whole' }, email: { name: 'whole', contact: 'whole' } },
    });
    component.select('source', 'title');
    component.select('target', 'name');
    component.cancel();
    assert.equal(component.selected, null);
    assert.equal(component.isIncompatible('target', 'name'), false);
    assert.deepEqual(component.mappings(), {});
});

test('required destination progress follows connections and removal', () => {
    const component = editor({}, false, { requiredTargets: ['name', 'contact'] });
    assert.deepEqual(component.missingRequired(), ['name', 'contact']);
    component.connect('title', 'name');
    assert.deepEqual(component.missingRequired(), ['contact']);
    component.remove('name');
    assert.deepEqual(component.missingRequired(), ['name', 'contact']);
});

test('server connection modes identify existing conflicts and allow compatible replacements', () => {
    const component = editor({ name: sourceEntry('title') }, false, {
        connectionModes: { title: { contact: 'whole' }, email: { name: 'whole', contact: 'whole' } },
    });
    assert.equal(component.hasTypeConflict('name'), true);
    component.connect('email', 'name');
    component.connect('title', 'contact');
    assert.deepEqual(component.mappings(), { name: sourceEntry('email'), contact: sourceEntry('title') });
    assert.equal(component.hasTypeConflict('name'), false);
});

test('multiple destinations preserve every line and remove only the selected source', () => {
    const state = { name: [sourceEntry('title'), sourceEntry('email')] };
    const component = editor(state, false, { connectionModes: itemModes, requiredTargets: ['name'] });
    component.refresh();

    assert.equal(component.connections.length, 2);
    assert.deepEqual(component.connections.map(({ source }) => source).sort(), ['email', 'title']);
    assert.equal(component.mappedTargetCount(), 1);
    assert.deepEqual(component.missingRequired(), []);
    component.connect('title', 'name');
    assert.deepEqual(component.mappings(), state);
    component.remove('name', 'title');
    assert.deepEqual(component.mappings(), { name: [sourceEntry('email')] });
    assert.equal(component.isDisabled('source', 'title'), false);
    assert.equal(component.isDisabled('source', 'email'), true);
    assert.deepEqual(component.missingRequired(), []);

    component.connect('title', 'contact');
    component.remove('name');
    assert.deepEqual(component.mappings(), { contact: sourceEntry('title') });
    assert.deepEqual(component.missingRequired(), ['name']);
});

test('conflicts are checked for every source in a multiple destination', () => {
    const component = editor({ name: [sourceEntry('title'), sourceEntry('email')] }, false, {
        connectionModes: { title: { name: 'item', contact: 'whole' }, email: { contact: 'whole' } },
    });
    assert.equal(component.hasTypeConflict('name'), true);
    component.remove('name', 'email');
    assert.equal(component.hasTypeConflict('name'), false);
    component.connect('email', 'name');
    assert.deepEqual(component.mappings(), { name: [sourceEntry('title')] });
});

test('removing the final line removes its state key and read-only mode preserves all lines', () => {
    const state = { name: [sourceEntry('title')] };
    const component = editor(state, true, { connectionModes: itemModes });
    component.connect('email', 'name');
    component.remove('name', 'title');
    assert.deepEqual(component.mappings(), state);
    component.disabled = false;
    component.remove('name', 'title');
    assert.deepEqual(component.mappings(), {});
    assert.equal(component.isMapped('target', 'name'), false);
});

for (const inverse of [false, true]) {
    const mode = inverse ? 'inverse' : 'forward';

    test(`${mode}: port clicks append a source and drawing follows the visible left-to-right direction`, () => {
        const component = editor({ name: [sourceEntry('title')] }, false, { inverse, connectionModes: itemModes });
        component.fieldBindings('target', 'name')['@click']();
        component.refresh();
        assert.equal(component.connections.length, 1);
        component.fieldBindings('source', 'email')['@click']();
        assert.deepEqual(component.mappings(), { name: [sourceEntry('title'), sourceEntry('email')] });
        component.refresh();
        assert.equal(component.connections.length, 2);
        const connection = component.connections.find(({ source }) => source === 'email');
        assert.match(connection.path, inverse ? /^M 88 48 .*388 128$/ : /^M 88 128 .*388 48$/);
        assert.deepEqual(connection.midpoint, { x: 238, y: 88 });
    });
}

test('empty Livewire state can be connected in either orientation', () => {
    for (const inverse of [false, true]) {
        for (const state of [null, []]) {
            const element = { querySelectorAll: () => [
                { dataset: { side: 'source', id: 'title' } },
                { dataset: { side: 'target', id: 'name' } },
            ] };
            const component = fieldMapping({
                element, state, inverse, disabled: false, connectionModes: itemModes,
                targetModes: { name: 'item', contact: 'whole' },
            });
            assert.deepEqual(component.state, []);
            assert.deepEqual(component.mappings(), {});
            component.connect('title', 'name');
            assert.deepEqual(component.mappings(), { name: [sourceEntry('title')] });
        }
    }
});

test('a static value occupies a destination without drawing a line or locking a source', () => {
    const component = editor({ name: value('Customer'), contact: sourceEntry('email') }, false, { requiredTargets: ['name'] });
    component.refresh();

    assert.equal(component.isConstant('name'), true);
    assert.equal(component.isConstant('contact'), false);
    assert.equal(component.isMapped('target', 'name'), true);
    assert.equal(component.mappedTargetCount(), 2);
    assert.deepEqual(component.missingRequired(), []);
    assert.deepEqual(component.connections.map((connection) => connection.target), ['contact']);
    assert.equal(component.isDisabled('source', 'title'), false);
});

test('static row changes are reflected in mappings and source connections replace their values', () => {
    const component = editor({ contact: sourceEntry('email'), name: value('') });
    component.state.find(row => row.kind === 'source' && row.source === 'email').target = null;
    component.state.push({ kind: 'static', target: 'contact', static: 'x', options: {} });

    component.connect('title', 'name');
    assert.deepEqual(component.mappings(), { contact: value('x'), name: sourceEntry('title') });
    component.remove('contact');
    assert.deepEqual(component.mappings(), { name: sourceEntry('title') });
});

test('whole arrays and item collections replace each other without flattening', () => {
    const component = editor({}, false, { connectionModes: {
        title: { name: 'whole' }, email: { name: 'item' },
    } });
    component.connect('email', 'name');
    assert.deepEqual(component.mappings(), { name: [sourceEntry('email')] });
    assert.equal(component.targetMode('name'), 'item');
    component.updateConfiguration({ title: { name: 'whole' } }, ['title', 'email'], {}, { name: 'whole', contact: 'whole' });
    component.connect('title', 'name');
    assert.deepEqual(component.mappings(), { name: sourceEntry('title') });
    assert.equal(component.targetMode('name'), 'whole');
    assert.equal(component.isDisabled('source', 'email'), false);
    assert.equal(component.isDisabled('target', 'name'), false);
    component.refresh();
    assert.equal(component.connections.length, 1);
    component.remove('name', 'title');
    assert.deepEqual(component.mappings(), {});
    component.updateConfiguration({ email: { name: 'item' } }, ['title', 'email'], {}, { name: 'item', contact: 'whole' });
    component.connect('email', 'name');
    assert.deepEqual(component.mappings(), { name: [sourceEntry('email')] });
});

test('connections and removals preserve arbitrary source settings', () => {
    const sourceSettings = { title: { alias: 'Name', enabled: false }, email: { format: 'lowercase' } };
    const component = editor({}, false, { sourceSettings, connectionModes: itemModes });
    component.connect('title', 'name');
    component.connect('email', 'name');
    component.remove('name', 'email');
    component.connect('email', 'contact');
    component.remove('name');
    assert.deepEqual(Object.fromEntries(component.state.filter(row => row.kind === 'source').map(row => [row.source, row.options])), sourceSettings);
    assert.deepEqual(component.mappings(), { contact: sourceEntry('email') });
});

for (const occupied of [sourceEntry('title'), value(false)]) {
    test(`occupied targets show availability from types during selection (${occupied.kind})`, () => {
        const state = { name: occupied, contact: value('') };
        const component = editor(state, false, {
            connectionModes: { title: { name: 'whole' }, email: { contact: 'whole' } },
        });
        component.select('source', 'email');
        assert.equal(component.rowClasses('target', 'name', false)['is-incompatible'], true);
        assert.equal(component.rowClasses('target', 'name', false)['is-compatible'], false);
        assert.equal(component.fieldBindings('target', 'name')[':aria-disabled'](), true);
        assert.equal(component.rowClasses('target', 'contact', false)['is-compatible'], true);
        component.fieldBindings('target', 'name')['@click']();
        assert.deepEqual(component.mappings(), state);
        assert.deepEqual(component.selected, { side: 'source', id: 'email' });
        component.fieldBindings('target', 'contact')['@click']();
        assert.deepEqual(component.mappings(), { name: occupied, contact: sourceEntry('email') });
        assert.equal(component.rowClasses('target', 'name', false)['is-incompatible'], false);
        assert.equal(component.rowClasses('target', 'contact', false)['is-compatible'], false);
    });
}

test('hover and focus highlight only the connection endpoints', () => {
    const component = editor({ name: [sourceEntry('title'), sourceEntry('email')] }, false, { connectionModes: itemModes });
    component.refresh();
    component.hoveredConnection = component.connections.find(({ source }) => source === 'title');
    assert.equal(component.rowClasses('source', 'title', false)['is-disconnecting'], true);
    assert.equal(component.rowClasses('target', 'name', false)['is-disconnecting'], true);
    assert.equal(component.rowClasses('source', 'email', false)['is-disconnecting'], false);
    assert.equal(component.rowClasses('target', 'contact', false)['is-disconnecting'], false);
    component.focusedConnection = component.connections.find(({ source }) => source === 'email');
    component.hoveredConnection = null;
    assert.equal(component.rowClasses('source', 'title', false)['is-disconnecting'], false);
    assert.equal(component.rowClasses('source', 'email', false)['is-disconnecting'], true);
    component.refresh();
    assert.equal(component.rowClasses('source', 'email', false)['is-disconnecting'], true);
    component.remove('name', 'email');
    component.refresh();
    assert.equal(component.focusedConnection, null);
    assert.equal(component.rowClasses('source', 'email', false)['is-disconnecting'], false);
});

test('disabled connections never highlight their endpoints', () => {
    const component = editor({ name: sourceEntry('title') }, true);
    component.refresh();
    component.hoveredConnection = component.connections[0];
    component.focusedConnection = component.connections[0];
    assert.equal(component.rowClasses('source', 'title', false)['is-disconnecting'], false);
    assert.equal(component.rowClasses('target', 'name', false)['is-disconnecting'], false);
});

for (const firstSide of ['source', 'target']) {
    test(`card event filtering connects ${firstSide} first and ignores interactive targets`, () => {
        const component = editor();
        const currentTarget = { contains: () => true };
        const group = {};
        const emptySpace = { currentTarget, target: { closest: selector => selector.includes('[role="group"]') ? group : null } };
        const control = { currentTarget, target: { closest: () => ({}) } };
        const firstId = firstSide === 'source' ? 'email' : 'contact';
        const secondSide = firstSide === 'source' ? 'target' : 'source';
        const secondId = firstSide === 'source' ? 'contact' : 'email';
        component.selectFromCard(control, firstSide, firstId);
        assert.equal(component.selected, null);
        component.selectFromCard(emptySpace, firstSide, firstId);
        assert.deepEqual(component.selected, { side: firstSide, id: firstId });
        component.selectFromCard(control, secondSide, secondId);
        assert.deepEqual(component.selected, { side: firstSide, id: firstId });
        assert.deepEqual(component.mappings(), {});
        component.selectFromCard(emptySpace, secondSide, secondId);
        assert.equal(component.selected, null);
        assert.deepEqual(component.mappings(), { contact: sourceEntry('email') });
    });
}

test('card event filtering preserves a pending connection for a marked interactive target', () => {
    const component = editor();
    const widget = {};
    const event = {
        target: { closest: selector => selector.split(', ').includes('[data-field-interactive]') ? widget : null },
        currentTarget: { contains: element => element === widget },
    };
    component.selectFromCard(event, 'source', 'email');
    assert.equal(component.selected, null);
    component.select('source', 'email');
    component.selectFromCard(event, 'target', 'contact');
    assert.deepEqual(component.selected, { side: 'source', id: 'email' });
    assert.deepEqual(component.mappings(), {});
});

test('an interactive group outside the card does not block empty card clicks', () => {
    const component = editor();
    component.selectFromCard({
        target: { closest: () => ({}) },
        currentTarget: { contains: () => false },
    }, 'source', 'email');
    assert.deepEqual(component.selected, { side: 'source', id: 'email' });
});

test('native editor rows keep their addresses while item connections preserve selection order', () => {
    const component = editor({}, false, { connectionModes: itemModes, staticDefaults: { name: false } });
    const title = { kind: 'source', source: 'title', target: null, options: { transforms: [{ type: 'cast' }] } };
    const email = { kind: 'source', source: 'email', target: null, options: { alias: 'Contact' } };
    component.state = [
        { kind: 'static', target: 'name', static: false, options: {} },
        title,
        email,
    ];

    component.connect('title', 'name');
    assert.equal(component.state[1], title);
    assert.equal(component.state[2], email);
    component.connect('email', 'name');
    assert.deepEqual(component.mappings(), { name: [sourceEntry('title'), sourceEntry('email')] });

    component.remove('name', 'title');
    assert.deepEqual(component.mappings(), { name: [sourceEntry('email')] });
    component.connect('title', 'name');
    assert.deepEqual(component.mappings(), { name: [sourceEntry('email'), sourceEntry('title')] });
    component.remove('name');
    assert.equal(component.state.length, 3);
    assert.equal(component.state[1], title);
    assert.equal(component.state[2], email);
    assert.equal(component.state[0].target, 'name');
    assert.equal(title.target, null);
    assert.equal(email.target, null);
    assert.deepEqual(component.mappings(), { name: value(false) });
});

for (const mode of ['item', 'property']) {
    test(`${mode} collects sources, removes one, and rechecks updated modes`, () => {
        const modes = { title: { name: mode }, email: { name: mode } };
        const component = editor({}, false, { connectionModes: modes });
        component.select('target', 'name');
        component.select('source', 'title');
        component.select('source', 'email');
        component.select('target', 'name');
        assert.deepEqual(component.mappings(), { name: [sourceEntry('title'), sourceEntry('email')] });
        assert.equal(component.targetMode('name'), mode);
        assert.equal(component.hasTypeConflict('name'), false);
        component.refresh();
        assert.equal(component.connections.length, 2);
        const rows = structuredClone(component.state);
        component.updateConfiguration({ title: {}, email: { name: mode } }, ['title', 'email'], {}, { name: mode, contact: 'whole' });
        assert.equal(component.hasTypeConflict('name'), true);
        assert.deepEqual(component.state, rows);
        component.remove('name', 'title');
        assert.deepEqual(component.mappings(), { name: [sourceEntry('email')] });
        assert.equal(component.hasTypeConflict('name'), false);
        component.updateConfiguration(scalarModes, ['title', 'email'], {}, { name: 'whole', contact: 'whole' });
        assert.equal(component.targetMode('name'), 'whole');
        component.connect('email', 'name');
        assert.deepEqual(component.mappings(), { name: sourceEntry('email') });
    });
}

test('whole destinations report conflicting submitted multiple connections', () => {
    const component = editor({ name: [sourceEntry('title'), sourceEntry('email')] });
    assert.equal(component.hasTypeConflict('name'), true);
});

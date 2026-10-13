import assert from 'node:assert/strict'
import test from 'node:test'
import { jsonSchemaPicker } from '../resources/js/json-schema-picker.js'
import jsonSchemaTools, { schemaSources, schemaGroups, schemaType, schemaNullable, schemaSelectionLabel, schemaEnums, schemaIcon, schemaColor, schemaConstraints } from '../resources/js/json-schema-tools.js'

const schema = { type: 'object', properties: {
    customer: { type: 'object', properties: {
        email: { type: ['string', 'null'], description: 'Delivery address' },
        phone: { type: 'string' },
    } },
    tags: { type: 'array', items: { type: 'object', properties: { name: { type: 'string' } } } },
} }

test('rebuilds adapter groups from current display options while preserving exact selection and readonly items', () => {
    const tools = jsonSchemaTools()
    const document = { properties: {
        'metadata.version': { type: 'string', format: 'uuid', minLength: 1, 'x-icon': 'version', 'x-color': '#abc' },
        tags: { type: 'array', items: { type: 'string' } },
    }, required: ['metadata.version'] }
    tools.schemaIcons = { version: '<svg></svg>' }
    tools.schemaPropertyTitles = { 'metadata.version': 'Metadata Version' }
    tools.schemaSelectedKey = '["metadata.version"]'
    const rebuild = () => {
        tools.schemaData = tools.schemaBuildGroups(document, 'Schema')
        tools.schemaSyncSelection()
    }
    rebuild()
    assert.equal(tools.schemaData[0].sources.some(source => source.key === '[]'), false)
    assert.deepEqual(tools.schemaData[0].sources.find(source => source.key === '["tags",null]').value, null)
    assert.equal(tools.schemaSelectedSource.type, 'string')
    assert.equal(tools.schemaSelectedSource.required, false)
    assert.deepEqual(tools.schemaSelectedSource.constraints, [])
    assert.equal(tools.schemaSelectedSource.title, 'Metadata Version')
    assert.equal(tools.schemaSelectedSource.iconHtml, '<svg></svg>')
    assert.equal(tools.schemaSelectedSource.color, '#abc')
    tools.schemaFormat = tools.schemaRequired = tools.schemaConstraints = true
    tools.schemaColors = false
    tools.schemaIcons = {}
    rebuild()
    assert.deepEqual(tools.schemaSelectedSource.value, ['metadata.version'])
    assert.equal(tools.schemaSelectedSource.type, 'uuid')
    assert.equal(tools.schemaSelectedSource.required, true)
    assert.deepEqual(tools.schemaSelectedSource.constraints[0].values, [{ kind: 'length', value: '≥ 1' }])
    assert.equal(tools.schemaSelectedSource.color, undefined)
    assert.equal(tools.schemaSelectedSource.iconHtml, null)
    assert.equal(tools.schemaSelectedSource.schema['x-color'], '#abc')
})

test('returns exact path segments and distinguishes dotted names from nested fields', () => {
    const document = { properties: {
        'metadata.version': { type: 'integer' },
        metadata: { properties: { version: { type: 'integer' } } },
    } }
    let value = null
    const picker = createPicker({
        groups: () => schemaGroups(document, 'Result', { includeRoot: false }),
        selected: source => source.key === JSON.stringify(value),
        choose: source => value = source.value,
    })
    const literal = picker.jsonSchemaData[0].sources.find(source => source.fieldName === 'metadata.version')
    const nested = picker.jsonSchemaData[0].sources.find(source => source.fieldName === 'version')

    assert.deepEqual(picker.jsonSchemaChoose(literal), ['metadata.version'])
    assert.deepEqual(value, ['metadata.version'])
    assert.equal(schemaSelectionLabel(value, 'Select a field'), 'metadata.version')
    picker.jsonSchemaSyncSelection()
    assert.equal(picker.jsonSchemaSelected(literal), true)
    assert.equal(picker.jsonSchemaSelected(nested), false)
    assert.deepEqual(picker.jsonSchemaChoose(nested), ['metadata', 'version'])
    assert.equal(schemaSelectionLabel(value, 'Select a field'), 'metadata → version')
    picker.jsonSchemaSyncSelection()
    assert.equal(picker.jsonSchemaSelected(nested), true)
    assert.equal(picker.jsonSchemaSelected(literal), false)
    assert.equal(picker.jsonSchemaVisible(nested), true)
})

test('omits the root row while keeping exact paths, depths and field selection', () => {
    const fields = schemaSources(schema, { includeRoot: false, descendArrays: true })
    assert.equal(fields.some(field => field.key === '[]'), false)
    assert.equal(fields[0].key, '["customer"]')
    assert.equal(fields[0].depth, 0)
    assert.equal(fields[0].hasChildren, true)
    const email = fields.find(field => field.key === '["customer","email"]')
    assert.equal(email.depth, 1)
    assert.deepEqual(email.ancestors, ['["customer"]'])
    const array = fields.find(field => field.key === '["tags",null,"name"]')
    assert.equal(array.depth, 2)
    assert.equal(array.value, null)
    assert.equal(array.compatible, false)
    assert.deepEqual(array.ancestors, ['["tags"]', '["tags",null]'])
    assert.deepEqual(schemaGroups({ type: 'object', properties: {} }, 'Result', { includeRoot: false }), [])
    const picker = createPicker({ groups: () => schemaGroups(schema, 'Result', { includeRoot: false }), choose: field => field.value })
    assert.deepEqual(picker.jsonSchemaChoose(picker.jsonSchemaData[0].sources[0]), ['customer'])
})

test('expands array item schemas under readonly wildcard rows without leaking restrictions to parents', () => {
    const document = { properties: {
        '*': { type: 'string' },
        tags: { type: 'array', maxItems: 10, uniqueItems: true, items: { type: 'string', maxLength: 30, enum: ['red', 'green'] } },
        matrix: { type: 'array', items: { type: 'array', minItems: 1, items: { type: 'number', minimum: 0 } } },
        records: { type: 'array', items: { type: 'object', properties: { '*': { type: 'string', maxLength: 10 } }, required: ['*'] } },
    } }
    const groups = schemaGroups(document, 'Schema', { includeRoot: false, descendArrays: true, constraints: true, format: true, required: true })
    const fields = groups[0].sources
    const tags = fields.find(field => field.key === '["tags"]')
    const item = fields.find(field => field.key === '["tags",null]')
    assert.deepEqual(tags.constraints[0].values, [{ kind: 'items', value: '≤ 10' }, { kind: 'unique', value: '' }])
    assert.deepEqual(tags.enums, [])
    assert.equal(tags.hasChildren, true)
    assert.equal(item.fieldName, '*')
    assert.equal(item.fieldLabel, 'tags → *')
    assert.equal(item.depth, 1)
    assert.deepEqual(item.constraints[0].values, [{ kind: 'length', value: '≤ 30' }])
    assert.deepEqual(item.enums[0].values, ['"red"', '"green"'])
    assert.equal(item.type, 'enum')
    assert.equal(item.value, null)
    const inner = fields.find(field => field.key === '["matrix",null,null]')
    assert.equal(inner.depth, 2)
    assert.deepEqual(inner.ancestors, ['["matrix"]', '["matrix",null]'])
    assert.deepEqual(inner.constraints[0].values, [{ kind: 'range', value: '≥ 0' }])
    assert.deepEqual(fields.find(field => field.key === '["*"]').value, ['*'])
    assert.equal(fields.find(field => field.key === '["records",null,"*"]').required, true)
    let chosen = null
    const picker = createPicker({ groups: () => groups, selected: source => source.key === '["tags"]', choose: source => chosen = source.value })
    assert.equal(picker.jsonSchemaSelected(item), false)
    assert.equal(picker.jsonSchemaVisible(item), false)
    picker.jsonSchemaToggle(tags)
    assert.equal(picker.jsonSchemaVisible(item), true)
    assert.equal(picker.jsonSchemaChoose(item), false)
    assert.equal(picker.jsonSchemaChoose(fields.find(field => field.key === '["records",null,"*"]')), false)
    assert.equal(chosen, null)
    assert.deepEqual(picker.jsonSchemaChoose(tags), ['tags'])
    picker.jsonSchemaToggle(tags)
    assert.equal(picker.jsonSchemaVisible(item), false)
})

test('shows titles and parent-object required markers independently while preserving exact paths', () => {
    const document = { properties: {
        'literal.key': { title: 'Contact details', properties: { 'slash/key': { type: 'string', title: 'Email address' } }, required: ['slash/key'] },
        optional: { type: 'string' },
    }, required: ['literal.key'] }
    const defaults = schemaSources(document)
    assert.equal(defaults[1].title, 'Contact details')
    assert.equal(defaults[1].required, false)
    assert.deepEqual(defaults[1].constraints, [])
    const fields = schemaSources(document, { required: true })
    assert.equal(fields[0].required, false)
    assert.equal(fields[1].title, 'Contact details')
    assert.equal(fields[1].required, true)
    assert.equal(fields[2].required, true)
    assert.equal(fields[3].required, false)
    assert.deepEqual(fields[2].value, ['literal.key', 'slash/key'])
    assert.equal(fields[2].fieldLabel, 'literal.key → slash/key')
    assert.equal(schemaSources(document, { required: true })[1].title, 'Contact details')
})

test('always uses explicit titles before generated property titles and keeps both searchable', () => {
    const document = { properties: {
        unit_price: { type: 'number' },
        contact_name: { type: 'string', title: 'Customer' },
        records: { type: 'array', items: { properties: { postalCode: { type: 'string' } } } },
        status: { anyOf: [{ type: 'string', title: 'Order status' }, { type: 'integer' }] },
    } }
    const propertyTitles = { unit_price: 'Unit Price', contact_name: 'Contact Name', records: 'Records', postalCode: 'Postal Code', status: 'Status' }
    const fields = schemaSources(document, { includeRoot: false, descendArrays: true, propertyTitles })
    assert.equal(fields[0].title, 'Unit Price')
    assert.equal(fields[1].title, 'Customer')
    assert.equal(fields.find(field => field.key === '["records",null]').title, undefined)
    assert.equal(fields.find(field => field.fieldName === 'postalCode').title, 'Postal Code')
    assert.equal(fields.find(field => field.fieldName === 'status').title, 'Order status')
    assert.deepEqual(fields[0].value, ['unit_price'])
    assert.equal(schemaSources({ properties: { constructor: { type: 'string' } } })[1].title, undefined)
    const picker = createPicker({ groups: () => schemaGroups(document, 'Result', { includeRoot: false, propertyTitles }) })
    picker.jsonSchemaSearch = 'unit price'
    picker.jsonSchemaSyncSearch()
    assert.deepEqual(picker.jsonSchemaMatches[0].sources.map(source => source.value), [['unit_price']])
})

test('resolves selected metadata from current schema and resets it when cleared or removed', () => {
    const tools = jsonSchemaTools()
    const document = { properties: {
        'metadata.version': { type: 'integer' },
        metadata: { properties: { version: { type: ['string', 'null'], description: 'API version', minLength: 1, enum: ['v1'], 'x-icon': 'code' } } },
    } }
    tools.schemaSelectedKey = '["metadata","version"]'
    tools.schemaData = schemaGroups(document, 'Result', { includeRoot: false, format: true, constraints: true, icons: { code: '<svg></svg>' } })
    tools.schemaSyncSelection()
    assert.equal(tools.schemaSelectedSource.fieldLabel, 'metadata → version')
    assert.equal(tools.schemaSelectedSource.description, 'API version')
    assert.equal(tools.schemaSelectedSource.type, 'enum')
    assert.equal(tools.schemaSelectedSource.nullable, true)
    assert.equal(tools.schemaSelectedSource.iconHtml, '<svg></svg>')
    assert.deepEqual(tools.schemaSelectedSource.constraints[0].values, [{ kind: 'length', value: '≥ 1' }])
    assert.deepEqual(tools.schemaSelectedSource.enums[0].values, ['"v1"'])
    tools.schemaData = schemaGroups(document, 'Result', { includeRoot: false })
    tools.schemaSyncSelection()
    assert.equal(tools.schemaSelectedSource.type, 'string')
    assert.deepEqual(tools.schemaSelectedSource.constraints, [])
    tools.schemaSelectedKey = 'null'
    tools.schemaSyncSelection()
    assert.equal(tools.schemaSelectedSource, null)
    tools.schemaSelectedKey = '["metadata.version"]'
    tools.schemaSyncSelection()
    assert.equal(tools.schemaSelectedSource.type, 'integer')
    tools.schemaData = []
    tools.schemaSyncSelection()
    assert.equal(tools.schemaSelectedSource, null)
})

test('marks union paths required only across every variant and reads array item requirements for entry', () => {
    const document = { anyOf: [
        { properties: { id: { type: 'string', title: 'Record identifier' }, sometimes: { type: 'string' }, only: { type: 'string' } }, required: ['id', 'sometimes', 'only'] },
        { properties: { id: { type: 'integer', title: 'Internal identifier' }, sometimes: { type: 'string' } }, required: ['id'] },
    ] }
    const fields = schemaSources(document, { required: true })
    assert.equal(fields[1].title, 'Record identifier')
    assert.equal(fields[1].required, true)
    assert.equal(fields[2].required, false)
    assert.equal(fields[3].required, false)
    const array = { properties: { rows: { type: 'array', items: { properties: { code: { type: 'string' } }, required: ['code'] } } } }
    assert.equal(schemaSources(array, { required: true }).length, 2)
    assert.equal(schemaSources(array, { required: true, descendArrays: true })[3].required, true)
})

test('displays supported value limits including zero bounds and scoped array and union constraints', () => {
    assert.deepEqual(schemaConstraints({ type: 'string', minLength: 0, maxLength: 20, pattern: '^<.+>$' })[0].values, [
        { kind: 'length', value: '0–20' }, { kind: 'pattern', value: '^<.+>$' },
    ])
    assert.deepEqual(schemaConstraints({ type: 'number', minimum: 0, maximum: 10, multipleOf: 0.5 })[0].values, [
        { kind: 'range', value: '≥ 0 · ≤ 10' }, { kind: 'multiple', value: '0.5' },
    ])
    assert.equal(schemaConstraints({ type: 'string', maxLength: 0 })[0].values[0].value, '≤ 0')
    assert.equal(schemaConstraints({ type: 'string', minLength: 1 })[0].values[0].value, '≥ 1')
    const document = { type: 'array', minItems: 0, maxItems: 10, uniqueItems: true, items: { anyOf: [
        { type: 'string', format: 'uuid', maxLength: 36 },
        { type: 'integer', minimum: 1 },
    ] } }
    const constraints = schemaConstraints(document, true)
    assert.deepEqual(constraints[0].values, [{ kind: 'items', value: '0–10' }, { kind: 'unique', value: '' }])
    assert.deepEqual(constraints.map(constraint => constraint.scope), [[]])
    const itemConstraints = schemaConstraints(document.items, true)
    assert.deepEqual(itemConstraints.map(constraint => constraint.scope), [[1], [2]])
    assert.equal(itemConstraints[0].type, 'uuid')
    assert.deepEqual(schemaConstraints({ type: 'array', uniqueItems: false }), [])
    assert.deepEqual(schemaSources(document)[0].constraints, [])
    assert.deepEqual(schemaSources(document, { constraints: true, format: true })[0].constraints, constraints)
})

test('keeps compact value-limit tooltips separate from enum badge tooltips', () => {
    const document = { type: 'array', minItems: 0, uniqueItems: true, items: { anyOf: [
        { type: 'string', pattern: '^<.+>$', enum: ['<script>', '', null] },
        { type: 'integer', minimum: 0, enum: [0, 1] },
    ] } }
    const labels = { limits: 'Value limits', enum: 'Allowed values', alternative: 'Alternative', items: 'Items',
        constraints: { items: 'Items', unique: 'unique', pattern: 'Pattern', range: 'Value' } }
    const picker = createPicker({ groups: () => [] })
    const tooltip = options => picker.jsonSchemaConstraintsTooltip(schemaSources(document, options)[0], labels)
    assert.equal(tooltip({}), '')
    const limits = tooltip({ constraints: true })
    assert.ok(limits.includes('Items: ≥ 0\nunique'))
    assert.equal(limits.includes('Pattern'), false)
    const item = schemaSources(document, { constraints: true, format: true, descendArrays: true })[1]
    const itemLimits = picker.jsonSchemaConstraintsTooltip(item, labels)
    assert.ok(itemLimits.includes('Alternative 1 (enum)\nPattern: ^<.+>$'))
    assert.ok(itemLimits.includes('Alternative 2 (enum)\nValue: ≥ 0'))
    assert.equal(limits.includes('Allowed values'), false)
    const enumeration = picker.jsonSchemaEnumTooltip(item, labels)
    assert.ok(enumeration.includes('Alternative 1 (enum)\n"<script>", "", null'))
    assert.ok(enumeration.includes('Alternative 2 (enum)\n0, 1'))
    assert.equal(enumeration.includes('Value limits'), false)
    assert.equal(tooltip({ format: true }), '')
    assert.equal(picker.jsonSchemaEnumTooltip(schemaSources(document)[0], labels), '')
})

test('formats label-free limits while retaining full descriptions and union scope in tooltips', () => {
    const picker = createPicker({ groups: () => [] })
    const labels = { alternative: 'Alternative', items: 'Items', constraints: {
        length: 'Length', items: 'Items', pattern: 'Pattern', range: 'Value', multiple: 'Multiple of', unique: 'unique',
    } }
    const limits = schemaConstraints({ type: 'string', minLength: 1, maxLength: 100, pattern: '^[A-Z0-9-]+$' })[0]
    assert.deepEqual(limits.values.map(value => picker.jsonSchemaConstraintValue(value)), ['1–100', '/^[A-Z0-9-]+$/'])
    assert.equal(picker.jsonSchemaConstraintTooltip(limits, limits.values[0], labels), 'Length: 1–100')
    assert.equal(picker.jsonSchemaConstraintTooltip(limits, limits.values[1], labels), 'Pattern: ^[A-Z0-9-]+$')

    const number = schemaConstraints({ type: 'number', minimum: 0, maximum: 99, multipleOf: 0.01 })[0]
    assert.deepEqual(number.values.map(value => picker.jsonSchemaConstraintValue(value)), ['≥ 0 · ≤ 99', '× 0.01'])
    assert.equal(picker.jsonSchemaConstraintTooltip(number, number.values[1], labels), 'Multiple of: 0.01')

    const array = schemaConstraints({ type: 'array', minItems: 1, maxItems: 10, uniqueItems: true })[0]
    assert.equal(picker.jsonSchemaConstraintValue(array.values[0]), '1–10')
    assert.equal(picker.jsonSchemaConstraintTooltip(array, array.values[1], labels), 'unique')
    const variant = schemaConstraints({ anyOf: [{ type: 'number', minimum: 0 }] })[0]
    assert.equal(picker.jsonSchemaConstraintTooltip(variant, variant.values[0], labels), 'Alternative 1 (number)\nValue: ≥ 0')
})

test('renders structured popovers with scoped limits and safely escaped JSON enum values', () => {
    const picker = createPicker({ groups: () => [] })
    const labels = { limits: 'Value limits', enum: 'Allowed values', alternative: 'Alternative', items: 'Items',
        constraints: { items: 'Items', unique: 'unique', pattern: 'Pattern', range: 'Value' } }
    const array = schemaSources({ type: 'array', maxItems: 10, uniqueItems: true }, { constraints: true })[0]
    const limits = picker.jsonSchemaConstraintsPopover(array, labels)
    assert.ok(limits.includes('>Value limits</div>'))
    assert.ok(limits.includes('>Items</dt>'))
    assert.ok(limits.includes('>≤ 10</dd>'))
    assert.ok(limits.includes('>unique</dt>'))
    assert.ok(limits.includes('>✓</dd>'))

    const document = { anyOf: [
        { type: 'string', pattern: '^<script>&".*$', enum: ['<img src=x onerror="alert(1)">', '1', '', '&'] },
        { type: 'integer', minimum: 0, enum: [1, true, null] },
    ] }
    const source = schemaSources(document, { constraints: true, format: true })[0]
    const popover = picker.jsonSchemaConstraintsPopover(source, labels)
    assert.ok(popover.includes('>Alternative 1</span>'))
    assert.ok(popover.includes('>Alternative 2</span>'))
    assert.ok(popover.includes('^&lt;script&gt;&amp;&quot;.*$'))
    assert.equal(popover.includes('<script>'), false)

    const enumeration = picker.jsonSchemaEnumPopover(source, labels)
    assert.ok(enumeration.includes('>Allowed values</div>'))
    assert.equal(enumeration.includes('<img'), false)
    assert.ok(enumeration.includes('&lt;img src=x onerror=\\&quot;alert(1)\\&quot;&gt;'))
    for (const value of ['&quot;1&quot;', '&quot;&quot;', '&quot;&amp;&quot;', '1', 'true', 'null']) {
        assert.ok(enumeration.includes('>' + value + '</code>'))
    }
    assert.equal(picker.jsonSchemaConstraintsTooltip(source, labels).includes('<div'), false)
    assert.equal(picker.jsonSchemaEnumTooltip(source, labels).includes('eidos-json-schema-popover'), false)
})

test('keeps titles visible while toggling required markers and value-limit search', () => {
    const tools = jsonSchemaTools()
    const document = { properties: { code: { type: 'string', title: 'Product identifier', pattern: '^SKU-', minLength: 3 } }, required: ['code'] }
    const picker = createPicker({ groups: () => tools.schemaGroups(document, 'Result', { required: tools.schemaRequired, constraints: tools.schemaConstraints }) })
    const field = () => picker.jsonSchemaData[0].sources[1]
    picker.jsonSchemaSearch = 'product'
    picker.jsonSchemaSyncSearch()
    assert.deepEqual(picker.jsonSchemaMatches[0].sources[0].value, ['code'])
    assert.equal(field().title, 'Product identifier')
    assert.equal(field().required, false)
    tools.schemaRequired = true
    tools.schemaConstraints = true
    picker.jsonSchemaSync()
    picker.jsonSchemaSearch = 'SKU-'
    picker.jsonSchemaSyncSearch()
    assert.equal(field().required, true)
    assert.deepEqual(picker.jsonSchemaMatches[0].sources[0].value, ['code'])
    tools.schemaConstraints = false
    picker.jsonSchemaSync()
    picker.jsonSchemaSyncSearch()
    assert.equal(field().title, 'Product identifier')
    assert.equal(field().required, true)
    assert.deepEqual(picker.jsonSchemaMatches, [])
})

test('searches exact property paths and all union titles locally and clears disabled search', () => {
    const document = { properties: { 'literal.key': { properties: { value: { anyOf: [
        { type: 'string', title: 'External identifier' },
        { type: 'integer', title: 'Internal identifier' },
    ] } } } } }
    let searchable = true
    const entry = createPicker({
        groups: () => schemaGroups(document, 'Schema fields', { includeRoot: false }),
        disabled: true,
        searchable: () => searchable,
    })
    for (const query of ['literal.key value', 'EXTERNAL identifier', 'internal IDENTIFIER']) {
        entry.jsonSchemaSearch = query
        entry.jsonSchemaSyncSearch()
        const matches = entry.jsonSchemaMatches[0].sources
        assert.deepEqual(matches.map(source => source.value), [['literal.key', 'value']])
        assert.equal(entry.jsonSchemaVisible(matches[0]), true)
        assert.equal(matches[0].title, 'External identifier')
    }
    searchable = false
    entry.jsonSchemaSyncSearch()
    assert.equal(entry.jsonSchemaSearch, '')
    assert.equal(entry.jsonSchemaSearching, false)
    assert.equal(entry.jsonSchemaMatches[0].sources.length, 2)
})

test('applies palette, HEX and RGBA colors to field presentation with scoped union precedence', () => {
    const document = { properties: {
        status: { type: 'string', 'x-color': 'success' },
        email: { type: 'string', 'x-color': '#3b82f680' },
        amount: { anyOf: [{ type: 'integer', 'x-color': 'rgba(30, 144, 255, 0.5)' }, { type: 'null', 'x-color': 'danger' }] },
        tags: { type: 'array', items: { type: 'string', 'x-color': 'warning' } },
        plain: { type: 'string' },
    } }
    const fields = schemaSources(document, { includeRoot: false, descendArrays: true })
    assert.deepEqual(fields.map(source => source.color), ['success', '#3b82f680', 'rgba(30, 144, 255, 0.5)', undefined, 'warning', undefined])
    assert.equal(schemaColor({ 'x-color': 'primary', anyOf: [{ 'x-color': 'danger' }] }), 'primary')
    const tools = jsonSchemaTools()
    assert.deepEqual(tools.jsonSchemaColorStyles('success'), {
        '--json-schema-field-color': 'var(--success-600)',
        '--json-schema-field-color-dark': 'var(--success-400)',
    })
    for (const color of ['#3b82f680', 'rgba(30, 144, 255, 0.5)']) {
        assert.deepEqual(tools.jsonSchemaColorStyles(color), {
            '--json-schema-field-color': color,
            '--json-schema-field-color-dark': color,
        })
    }
    assert.deepEqual(tools.jsonSchemaColorStyles(undefined), {
        '--json-schema-field-color': '',
        '--json-schema-field-color-dark': '',
    })
    const picker = createPicker({ groups: () => schemaGroups(document, 'Result') })
    assert.deepEqual(picker.jsonSchemaColorStyles('success'), tools.jsonSchemaColorStyles('success'))
    tools.schemaData = schemaGroups(document, 'Result', { includeRoot: false })
    tools.schemaSelectedKey = '["email"]'
    tools.schemaSyncSelection()
    assert.equal(tools.schemaSelectedSource.color, '#3b82f680')
    assert.deepEqual(tools.schemaSelectedSource.value, ['email'])
})

test('toggles palette and literal colors without changing schema annotations, icons or selected paths', () => {
    const document = { properties: {
        email: { type: 'string', 'x-color': '#2563eb', 'x-icon': 'envelope' },
        tags: { type: 'array', 'x-color': 'primary', items: { type: 'string', 'x-color': 'rgba(147, 51, 234, 0.8)' } },
    } }
    const tools = jsonSchemaTools()
    const options = { includeRoot: false, descendArrays: true, icons: { envelope: '<svg></svg>' } }
    tools.schemaSelectedKey = '["email"]'
    for (const colors of [true, false, true]) {
        tools.schemaData = schemaGroups(document, 'Result', { ...options, colors })
        tools.schemaSyncSelection()
        assert.equal(tools.schemaSelectedSource.color, colors ? '#2563eb' : undefined)
        assert.deepEqual(tools.schemaSelectedSource.value, ['email'])
        assert.equal(tools.schemaSelectedSource.iconHtml, '<svg></svg>')
        assert.equal(tools.schemaSelectedSource.schema['x-color'], '#2563eb')
        assert.deepEqual(tools.schemaData[0].sources.map(source => source.color), colors
            ? ['#2563eb', 'primary', 'rgba(147, 51, 234, 0.8)']
            : [undefined, undefined, undefined])
        assert.deepEqual(tools.jsonSchemaColorStyles(tools.schemaSelectedSource.color), colors
            ? { '--json-schema-field-color': '#2563eb', '--json-schema-field-color-dark': '#2563eb' }
            : { '--json-schema-field-color': '', '--json-schema-field-color-dark': '' })
    }
})

test('uses registered icon markup for root, nested, union and array item fields', () => {
    const icons = { 'heroicon-o-user': '<svg aria-hidden="true"></svg>', 'heroicon-o-envelope': '<svg viewBox="0 0 24 24"></svg>' }
    const document = { type: 'object', 'x-icon': 'heroicon-o-user', properties: {
        customer: { type: 'object', 'x-icon': 'heroicon-o-user', properties: {
            email: { type: 'string', 'x-icon': 'heroicon-o-envelope' },
            name: { type: 'string' },
        } },
        contacts: { type: 'array', items: { properties: { email: { type: 'string', 'x-icon': 'heroicon-o-envelope' } } } },
    } }
    const fields = schemaSources(document, { icons, descendArrays: true })
    assert.equal(fields[0].iconHtml, icons['heroicon-o-user'])
    assert.equal(fields.find(source => source.key === '["customer","email"]').iconHtml, icons['heroicon-o-envelope'])
    assert.equal(fields.find(source => source.key === '["customer","name"]').iconHtml, null)
    assert.equal(fields.find(source => source.key === '["contacts",null,"email"]').iconHtml, icons['heroicon-o-envelope'])
    assert.equal(schemaGroups(document, 'Result', { icons })[0].hasIcons, true)
    assert.equal(schemaGroups({ properties: { value: { type: 'string' } } }, 'Result')[0].hasIcons, false)
    assert.equal(schemaIcon({ anyOf: [{ type: 'string' }, { type: 'integer', 'x-icon': 'heroicon-o-user' }] }), 'heroicon-o-user')
    const merged = schemaSources({ anyOf: [
        { properties: { value: { type: 'string', 'x-icon': 'heroicon-o-user' } } },
        { properties: { value: { type: 'integer', 'x-icon': 'heroicon-o-envelope' } } },
    ] }, { icons })[1]
    assert.equal(merged.icon, 'heroicon-o-user')
    assert.deepEqual(merged.value, ['value'])
    assert.equal(schemaSources({ properties: { value: { type: 'string', 'x-icon': '<img onerror="alert(1)">' } } })[1].iconHtml, null)
    assert.equal(schemaSources({ properties: { value: { type: 'string', 'x-icon': 'constructor' } } })[1].iconHtml, null)
})

test('uses exact property paths with descriptions, nullable types and separate array entry rendering', () => {
    const fields = schemaSources(schema)
    assert.deepEqual(fields[0].value, [])
    const email = fields.find(field => field.key === '["customer","email"]')
    assert.equal(email.fieldName, 'email')
    assert.equal(email.fieldLabel, 'customer → email')
    assert.equal(email.schema.description, 'Delivery address')
    assert.equal(email.description, 'Delivery address')
    assert.equal(email.type, 'string')
    assert.equal(schemaNullable(email.schema), true)
    assert.equal(email.nullable, true)
    assert.deepEqual(email.ancestors, ['["customer"]'])
    assert.equal(fields.find(field => field.key === '["customer"]').hasChildren, true)
    assert.equal(fields.some(field => field.key === '["tags",null,"name"]'), false)
    assert.equal(schemaSources(schema, { descendArrays: true }).some(field => field.key === '["tags",null,"name"]'), true)
    const exact = schemaSources({ properties: { 'literal.key': { properties: { 'slash/key': { type: 'boolean' } } } } })
    assert.equal(exact[2].key, '["literal.key","slash/key"]')
    assert.deepEqual(exact[2].value, ['literal.key', 'slash/key'])
    assert.equal(schemaSelectionLabel(exact[2].value, 'Select a field'), 'literal.key → slash/key')
    assert.equal(schemaSelectionLabel(null, 'Select a field'), 'Select a field')
})

test('combines union fields without synthetic variant path segments', () => {
    const fields = schemaSources({ anyOf: [
        { type: 'object', properties: { value: { type: 'string', description: 'Original description' } } },
        { type: 'object', properties: { value: { type: ['integer', 'null'] } } },
    ] })
    assert.deepEqual(fields.map(field => field.key), ['[]', '["value"]'])
    assert.equal(fields[1].type, 'string | integer')
    assert.equal(schemaNullable(fields[1].schema), true)
    assert.equal(fields[1].schema.description, 'Original description')
    assert.equal(schemaType({ anyOf: [{ type: 'null' }, { type: 'string' }] }), 'string')
    const picker = createPicker({ groups: () => [{ key: 'input', label: 'Input', sources: [fields[1]] }] })
    picker.jsonSchemaSearch = 'integer'
    picker.jsonSchemaSyncSearch()
    assert.equal(picker.jsonSchemaMatches[0].sources[0].key, '["value"]')
})

test('replaces types with enum badges through the format option and preserves JSON values', () => {
    const document = { type: 'object', properties: {
        status: { type: 'string', description: 'Order state', format: 'custom-format', enum: ['paid', '1', 1, true, null, '', '<script>', { key: 'value' }] },
    } }
    const hidden = schemaSources(document)[1]
    assert.equal(hidden.description, 'Order state')
    assert.equal(hidden.type, 'string')
    assert.deepEqual(hidden.enums, [])
    const formatted = schemaSources(document, { format: true })[1]
    assert.equal(formatted.type, 'enum')
    assert.deepEqual(formatted.enums[0].values, ['"paid"', '"1"', '1', 'true', 'null', '""', '"<script>"', '{"key":"value"}'])
    assert.equal(schemaType({ type: 'integer', enum: [1, 2] }, true), 'enum')
    assert.equal(schemaType({ type: 'array', items: document.properties.status }, true), 'enum[]')
    assert.equal(schemaType({ anyOf: [document.properties.status, { type: 'integer' }] }, true), 'enum | integer')
    assert.equal(schemaType({ type: ['string', 'null'], enum: ['paid', null] }, true), 'enum')
    assert.equal(schemaNullable({ type: ['string', 'null'], enum: ['paid', null] }), true)
    assert.equal(schemaSources({ properties: { email: { type: 'string', format: 'email', enum: ['user@example.com'] } } })[1].description, undefined)
    const nullable = { type: ['string', 'null'], format: 'uuid' }
    assert.equal(schemaType(nullable, true), 'uuid')
    assert.equal(schemaNullable(nullable), true)
    assert.equal(schemaType({ type: 'array', items: nullable }, true), 'uuid[]')
    assert.equal(schemaType({ anyOf: [nullable, { type: 'string', format: 'email' }, { type: 'integer' }, { type: 'string' }] }, true), 'uuid | email | integer | string')
})

test('keeps enum restrictions scoped to anyOf branches and array items', () => {
    const enums = schemaEnums({ anyOf: [
        { type: 'string', format: 'email', enum: ['user@example.com'] },
        { anyOf: [{ type: 'integer', enum: [1, 2] }, { type: 'string' }] },
    ] }, true)
    assert.deepEqual(enums.map(enumeration => enumeration.scope), [[1], [2, 1]])
    assert.equal(enums[0].type, 'enum')
    assert.deepEqual(enums[0].values, ['"user@example.com"'])
    assert.deepEqual(enums[1].values, ['1', '2'])
    const fields = schemaSources({ anyOf: [
        { properties: { kind: { type: 'string', enum: ['purchase'] } } },
        { properties: { kind: { type: 'string', enum: ['subscription'] } } },
    ] }, { format: true })
    assert.deepEqual(fields[1].enums.map(enumeration => enumeration.values), [['"purchase"'], ['"subscription"']])
    assert.deepEqual(fields[1].enums.map(enumeration => enumeration.scope), [[1], [2]])
})

test('searches enabled format and enum restrictions including array item values', () => {
    const document = { properties: {
        email: { type: 'string', description: 'Contact address', format: 'email' },
        status: { type: 'string', enum: ['paid', 'pending'] },
        channels: { type: 'array', items: { type: 'string', enum: ['webhook'] } },
    } }
    for (const [options, query, values] of [
        [{ format: true }, 'email', [['email']]],
        [{ format: true }, 'pending', [['status']]],
        [{ format: true, descendArrays: true }, 'webhook', [null]],
        [{}, 'pending', []],
    ]) {
        const picker = createPicker({ groups: () => schemaGroups(document, 'Result', options) })
        picker.jsonSchemaSearch = query
        picker.jsonSchemaSyncSearch()
        assert.deepEqual(picker.jsonSchemaMatches.flatMap(group => group.sources.map(source => source.value)), values)
    }
})

test('updates enum badges and tooltip values together when formats change', () => {
    const tools = jsonSchemaTools()
    const document = { properties: { id: { type: 'string', format: 'uuid', enum: ['sample-id'] } } }
    const picker = createPicker({ groups: () => tools.schemaGroups(document, 'Result', { format: tools.schemaFormat }) })
    const field = () => picker.jsonSchemaData[0].sources[1]
    assert.equal(field().type, 'string')
    assert.deepEqual(field().enums, [])
    tools.schemaFormat = true
    picker.jsonSchemaSync()
    assert.equal(field().type, 'enum')
    assert.deepEqual(field().enums[0].values, ['"sample-id"'])
    tools.schemaFormat = false
    picker.jsonSchemaSync()
    assert.equal(field().type, 'string')
    assert.deepEqual(field().enums, [])
})

test('reveals the selected path and allows its parent to be collapsed manually', () => {
    const groups = schemaGroups(schema, 'Result')
    const picker = createPicker({ groups: () => groups, selected: field => field.key === '["customer","email"]' })
    const parent = groups[0].sources.find(field => field.key === '["customer"]')
    const email = groups[0].sources.find(field => field.key === '["customer","email"]')
    assert.equal(picker.jsonSchemaVisible(email), true)
    picker.jsonSchemaToggle(parent)
    assert.equal(picker.jsonSchemaVisible(email), false)
    picker.jsonSchemaToggle(parent)
    assert.equal(picker.jsonSchemaVisible(email), true)
})

test('reveals a selected field in a large tree while keeping unrelated branches collapsed', () => {
    const document = { type: 'object', properties: Object.fromEntries(Array.from({ length: 100 }, (_, index) => [
        `object${index}`, { type: 'object', properties: {
            first: { type: 'string' }, second: { type: 'string' }, third: { type: 'string' },
        } },
    ])) }
    const groups = schemaGroups(document, 'Result')
    const picker = createPicker({ groups: () => groups, selected: field => field.key === '["object99","third"]' })

    const visible = picker.jsonSchemaMatches[0].sources.filter(source => picker.jsonSchemaVisible(source))
    assert.equal(visible.some(source => picker.jsonSchemaSelected(source)), true)
    assert.equal(visible.some(source => source.fieldLabel === 'object99 → third'), true)
    assert.equal(visible.some(source => source.fieldLabel === 'object0 → third'), false)
})

test('reveals an externally selected path and lets readonly branches expand without a selected path', () => {
    const groups = schemaGroups(schema, 'Result')
    let value = null
    const picker = createPicker({ groups: () => groups, selected: field => field.key === JSON.stringify(value) })
    const email = groups[0].sources.find(field => field.key === '["customer","email"]')
    assert.equal(picker.jsonSchemaVisible(email), false)

    value = ['customer', 'email']
    picker.jsonSchemaSyncSelection()

    assert.equal(picker.jsonSchemaVisible(email), true)
    assert.equal(picker.jsonSchemaSelected(email), true)
    const entry = createPicker({ groups: () => groups, disabled: true })
    entry.jsonSchemaSyncSelection()
    assert.equal(entry.jsonSchemaSelected(email), false)
    assert.equal(entry.jsonSchemaVisible(email), false)
    entry.jsonSchemaToggle(groups[0].sources.find(field => field.key === '["customer"]'))
    assert.equal(entry.jsonSchemaVisible(email), true)
})

test('searches schema descriptions and types and blocks incompatible or readonly selection', () => {
    const groups = schemaGroups(schema, 'Result')
    let selected = null
    const picker = createPicker({ groups: () => groups, choose: field => { selected = field.value; return true } })
    picker.jsonSchemaSearch = 'DELIVERY string'
    picker.jsonSchemaSyncSearch()
    const matches = picker.jsonSchemaMatches[0].sources
    assert.deepEqual(matches.map(field => field.key), ['["customer","email"]'])
    assert.equal(picker.jsonSchemaVisible(matches[0]), true)
    assert.equal(picker.jsonSchemaChoose({ ...matches[0], compatible: false }), false)
    assert.equal(selected, null)
    assert.equal(picker.jsonSchemaChoose(matches[0]), true)
    assert.deepEqual(selected, ['customer', 'email'])
    const entry = createPicker({ groups: () => groups, choose: () => { throw new Error('Readonly selection') }, disabled: true })
    assert.equal(entry.jsonSchemaChoose(matches[0]), false)
    picker.jsonSchemaSearch = 'unavailable'
    picker.jsonSchemaSyncSearch()
    assert.deepEqual(picker.jsonSchemaMatches, [])
})

test('replaces the visible fields when the schema changes', () => {
    let document = schema
    const picker = createPicker({ groups: () => schemaGroups(document, 'Result') })
    assert.equal(picker.jsonSchemaMatches[0].sources.some(field => field.key === '["customer"]'), true)

    document = { type: 'object', properties: { count: { type: 'integer' } } }
    picker.jsonSchemaSync()
    picker.jsonSchemaSyncSearch()

    assert.deepEqual(picker.jsonSchemaMatches[0].sources.map(field => field.key), ['[]', '["count"]'])
})

test('updates external search and selection independently', () => {
    let search = ''
    let value = null
    const picker = createPicker({
        groups: () => schemaGroups(schema, 'Result'),
        selected: source => source.key === JSON.stringify(value),
        search: () => search,
    })
    search = 'delivery STRING'
    picker.jsonSchemaSyncSearch()

    assert.deepEqual(picker.jsonSchemaMatches[0].sources.map(source => source.value), [['customer', 'email']])
    assert.equal(picker.jsonSchemaSearching, true)
    value = ['customer', 'email']
    picker.jsonSchemaSyncSelection()
    assert.equal(picker.jsonSchemaSelected(picker.jsonSchemaMatches[0].sources[0]), true)
    search = ''
    picker.jsonSchemaSyncSearch()
    assert.equal(picker.jsonSchemaMatches[0].sources.some(source => source.value?.join('.') === 'customer.phone'), true)
    assert.equal(picker.jsonSchemaSelected(picker.jsonSchemaMatches[0].sources.find(source => source.key === '["customer","email"]')), true)
    assert.equal(picker.jsonSchemaSearching, false)
})

function createPicker(options) {
    const picker = jsonSchemaPicker(options)
    picker.jsonSchemaSync()
    picker.jsonSchemaSyncSelection()
    picker.jsonSchemaSyncSearch()
    return picker
}

test('highlights and reveals the current readonly path without allowing another field to be chosen', () => {
    let value = ['customer', 'email']
    const groups = schemaGroups(schema, 'Schema', { includeRoot: false, descendArrays: true })
    const entry = createPicker({
        groups: () => groups,
        selected: source => source.key === JSON.stringify(value),
        choose: source => { value = source.value },
        disabled: true,
    })
    const email = groups[0].sources.find(source => source.fieldName === 'email')
    const phone = groups[0].sources.find(source => source.fieldName === 'phone')
    const arrayItem = groups[0].sources.find(source => source.fieldName === 'name')

    assert.equal(entry.jsonSchemaSelected(email), true)
    assert.equal(entry.jsonSchemaSelected(phone), false)
    assert.equal(entry.jsonSchemaVisible(email), true)
    assert.equal(entry.jsonSchemaChoose(phone), false)
    assert.deepEqual(value, ['customer', 'email'])
    assert.equal(entry.jsonSchemaSelected(arrayItem), false)

    value = ['customer', 'phone']
    entry.jsonSchemaSyncSelection()
    assert.equal(entry.jsonSchemaSelected(email), false)
    assert.equal(entry.jsonSchemaSelected(phone), true)

    value = null
    entry.jsonSchemaSyncSelection()
    assert.equal(entry.jsonSchemaSelected(phone), false)
})


test('traverses oneOf and allOf properties with scoped limits and required markers', () => {
    const definition = { type: 'object', properties: {
        payment: { type: 'object', properties: { token: { type: 'string' } }, oneOf: [
            { type: 'object', properties: { card: { type: 'object', properties: { number: { type: 'string' } } } }, required: ['card'] },
            { type: 'object', required: ['token'] },
        ] },
        quantity: { allOf: [{ type: 'integer', minimum: 1 }, { type: 'integer', maximum: 10 }] },
    }, allOf: [{ type: 'object', required: ['quantity'] }] }
    const sources = schemaSources(definition, { includeRoot: false, required: true, constraints: true })
    const number = sources.find(source => source.key === '["payment","card","number"]')
    assert.deepEqual(number.value, ['payment', 'card', 'number'])
    const quantity = sources.find(source => source.key === '["quantity"]')
    assert.equal(quantity.type, 'integer')
    assert.equal(quantity.required, true)
    assert.deepEqual(quantity.constraints.map(constraint => constraint.scope), [['allOf 1'], ['allOf 2']])
    assert.equal(schemaNullable({ allOf: [{ type: ['string', 'null'] }, { type: 'string' }] }), false)
    assert.equal(schemaNullable({ oneOf: [{ type: ['string', 'null'] }, { type: ['number', 'null'] }] }), false)
})

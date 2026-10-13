export function schemaType(schema, format = false) {
    if (format && schema?.enum) return 'enum'
    if (format && schema?.format) return schema.format
    for (const keyword of ['anyOf', 'oneOf', 'allOf']) {
        if (schema?.[keyword]) return [...new Set(schema[keyword].map(value => schemaType(value, format)).filter(type => type !== 'null'))].join(keyword === 'allOf' ? ' & ' : ' | ') || 'null'
    }
    const types = (Array.isArray(schema?.type) ? schema.type : [schema?.type]).filter(type => type && type !== 'null')
    if (['object', 'array', 'string', 'number', 'boolean'].every(type => types.includes(type))) return 'any'
    if (types.length === 1 && types[0] === 'array' && schema?.items) return `${schemaType(schema.items, format)}[]`
    return types.join(' | ') || (schemaNullable(schema) || schema?.type === 'null' ? 'null' : 'any')
}

export function schemaNullable(schema) {
    return schema?.type === 'null'
        || Array.isArray(schema?.type) && schema.type.includes('null')
        || (schema?.anyOf ?? []).some(schemaNullable)
        || (schema?.oneOf ?? []).filter(schemaNullable).length === 1
        || Boolean(schema?.allOf?.length && schema.allOf.every(schemaNullable))
}

export function schemaIcon(schema) {
    return schemaAnnotation(schema, 'x-icon')
}

export function schemaColor(schema) {
    return schemaAnnotation(schema, 'x-color')
}

function schemaAnnotation(schema, name) {
    return schema[name] ?? schemaBranches(schema).map(variant => schemaAnnotation(variant, name)).find(value => value !== undefined)
}

function* schemaVariants(schema, scope = []) {
    yield { schema, scope }
    for (const keyword of ['anyOf', 'oneOf', 'allOf']) {
        for (const [index, variant] of (schema[keyword] ?? []).entries()) yield* schemaVariants(variant, [...scope, keyword === 'anyOf' ? index + 1 : `${keyword} ${index + 1}`])
    }
}

function schemaTitles(schema) {
    return [schema.title, ...schemaBranches(schema).flatMap(schemaTitles)].filter(title => title !== undefined)
}

export function schemaRequired(schema, path) {
    if (!path.length || !schema) return false
    const [name, ...nested] = path
    const property = name === null ? schema.items : schema.properties?.[name]
    const required = nested.length ? schemaRequired(property, nested) : schema.required?.includes(name)
    return Boolean(required
        || schema.anyOf?.length && schema.anyOf.every(variant => schemaRequired(variant, path))
        || schema.oneOf?.length && schema.oneOf.every(variant => schemaRequired(variant, path))
        || schema.allOf?.some(variant => schemaRequired(variant, path)))
}

export function schemaConstraints(schema, format = false) {
    const constraints = []
    const range = (min, max) => min !== undefined && max !== undefined ? `${min}–${max}`
        : min !== undefined ? `≥ ${min}` : `≤ ${max}`
    for (const { schema: value, scope } of schemaVariants(schema)) {
        const values = []
        if (value.minLength !== undefined || value.maxLength !== undefined) values.push({ kind: 'length', value: range(value.minLength, value.maxLength) })
        if (value.pattern !== undefined) values.push({ kind: 'pattern', value: value.pattern })
        if (value.minimum !== undefined || value.maximum !== undefined) values.push({ kind: 'range', value: [
            value.minimum !== undefined ? `≥ ${value.minimum}` : null,
            value.maximum !== undefined ? `≤ ${value.maximum}` : null,
        ].filter(value => value !== null).join(' · ') })
        if (value.multipleOf !== undefined) values.push({ kind: 'multiple', value: String(value.multipleOf) })
        if (value.minItems !== undefined || value.maxItems !== undefined) values.push({ kind: 'items', value: range(value.minItems, value.maxItems) })
        if (value.uniqueItems === true) values.push({ kind: 'unique', value: '' })
        if (values.length) constraints.push({ scope, type: schemaType(value, format), values })
    }
    return constraints
}

export function schemaEnums(schema, format = false) {
    const enums = []
    for (const { schema: value, scope } of schemaVariants(schema)) {
        if (value.enum) enums.push({ scope, type: schemaType(value, format), values: value.enum.map(value => JSON.stringify(value)) })
    }
    return enums
}

export function schemaSources(schema, { whole = 'All data', includeRoot = true, descendArrays = false, format = false, required = false, constraints = false, icons = {}, colors = true, propertyTitles = {} } = {}) {
    if (schema === null || schema === undefined) return []
    schema = resolveSchemaReferences(schema)
    const sources = new Map()
    const append = (value, path) => {
        const key = JSON.stringify(path)
        const existing = sources.get(key)
        if (existing) {
            existing.schema = { anyOf: [existing.schema, value], description: existing.schema.description ?? value.description }
            return
        }
        sources.set(key, {
            key, value: path.includes(null) ? null : path, schema: value,
            compatible: !path.includes(null),
            propertyTitle: path.length && path.at(-1) !== null && Object.hasOwn(propertyTitles, path.at(-1)) ? propertyTitles[path.at(-1)] : undefined,
            required: required && schemaRequired(schema, path),
            fieldName: path.at(-1) ?? (path.length ? '*' : whole), fieldLabel: path.map(part => part === null ? '*' : part).join(' → ') || whole,
            depth: Math.max(0, path.length - 1),
            ancestors: path.slice(0, -1).map((_, index) => JSON.stringify(path.slice(0, index + 1))),
            hasChildren: false,
        })
    }
    const properties = (value, path = []) => {
        for (const variant of schemaBranches(value)) properties(variant, path)
        for (const [name, field] of Object.entries(value?.properties ?? {})) {
            const child = [...path, name]
            append(field, child)
            properties(field, child)
        }
        if (descendArrays && value?.items) {
            // Null identifies an item schema without colliding with literal "*" properties.
            const child = [...path, null]
            append(value.items, child)
            properties(value.items, child)
        }
    }
    if (includeRoot) append(schema, [])
    properties(schema)
    for (const source of sources.values()) {
        source.type = schemaType(source.schema, format)
        source.nullable = schemaNullable(source.schema)
        source.description = source.schema.description
        source.titles = schemaTitles(source.schema)
        if (!source.titles.length && source.propertyTitle !== undefined) source.titles = [source.propertyTitle]
        source.title = source.titles[0]
        source.color = colors ? schemaColor(source.schema) : undefined
        source.icon = schemaIcon(source.schema)
        source.iconHtml = source.icon !== undefined && Object.hasOwn(icons, source.icon) ? icons[source.icon] : null
        source.enums = format ? schemaEnums(source.schema, format) : []
        source.constraints = constraints ? schemaConstraints(source.schema, format) : []
        if (source.ancestors.length) sources.get(source.ancestors.at(-1)).hasChildren = true
    }
    return [...sources.values()]
}

export function schemaGroups(schema, label, options = {}) {
    const sources = schemaSources(schema, options)
    return sources.length ? [{ key: label, label, sources, hasIcons: sources.some(source => source.iconHtml) }] : []
}

export function schemaSelectionLabel(value, placeholder) {
    return value?.join(' → ') ?? placeholder
}

function schemaBranches(schema) {
    return ['anyOf', 'oneOf', 'allOf'].flatMap(keyword => schema?.[keyword] ?? [])
}

function resolveSchemaReferences(root) {
    const resolve = (schema, refs = []) => {
        if (schema === null || typeof schema !== 'object') return schema
        let definition = { ...schema }
        while (typeof definition.$ref === 'string') {
            const { $ref: ref, ...siblings } = definition
            if (refs.includes(ref)) throw new Error(`Circular JSON Schema $ref [${ref}] detected.`)
            refs = [...refs, ref]
            if (ref !== '#' && !ref.startsWith('#/')) throw new Error(`Unable to resolve non-local JSON Schema $ref [${ref}].`)
            let target = root
            for (const part of ref === '#' ? [] : ref.slice(2).split('/')) {
                const name = decodeURIComponent(part).replaceAll('~1', '/').replaceAll('~0', '~')
                if (target === null || typeof target !== 'object' || !Object.hasOwn(target, name)) throw new Error(`Unable to resolve JSON Schema $ref [${ref}].`)
                target = target[name]
            }
            if (target === null || typeof target !== 'object' || Array.isArray(target)) throw new Error(`The JSON Schema $ref [${ref}] does not point to a schema.`)
            definition = { ...target, ...siblings }
        }
        if (definition.properties) definition.properties = Object.fromEntries(Object.entries(definition.properties).map(([name, property]) => [name, resolve(property, refs)]))
        for (const keyword of ['anyOf', 'oneOf', 'allOf']) {
            if (definition[keyword]) definition[keyword] = definition[keyword].map(variant => resolve(variant, refs))
        }
        if (definition.items) definition.items = resolve(definition.items, refs)
        return definition
    }
    return resolve(root)
}

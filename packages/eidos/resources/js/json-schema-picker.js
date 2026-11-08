import { schemaPresentation } from './json-schema-presentation.js'

export function jsonSchemaPicker({ groups, selected = () => false, choose = null, search = null, searchable = () => true, disabled = false, labels = { variant: 'Variant' } }) {
    return {
        ...schemaPresentation,
        jsonSchemaSelectedKeys: new Set(),
        jsonSchemaSelectedAncestors: new Set(),
        jsonSchemaData: [],
        jsonSchemaMatches: [],
        jsonSchemaSearch: '',
        jsonSchemaSearching: false,
        jsonSchemaExpanded: {},
        jsonSchemaBranchKeys: new Set(),
        jsonSchemaSync() {
            this.jsonSchemaData = groups().map(group => {
                const hidden = new Set(group.sources.filter(source => source.arrayItem).map(source => source.key))
                const root = group.sources.find(source => source.key === '[]' && source.arrayItems.length)
                const needsRoot = source => root && source !== root && !source.ancestors.includes(root.key)
                const sources = group.sources.filter(source => !source.arrayItem).map(source => ({
                    ...source,
                    ancestors: [
                        ...(needsRoot(source) ? [root.key] : []),
                        ...source.ancestors.filter(key => !hidden.has(key)),
                    ],
                    depth: source.depth - source.ancestors.filter(key => hidden.has(key)).length + (needsRoot(source) ? 1 : 0),
                }))
                const sourcesByKey = new Map(sources.map(source => [source.key, source]))
                const parents = new Set(sources.map(source => source.ancestors.at(-1)))
                const selectableAncestors = new Set(sources
                    .filter(source => source.compatible !== false && source.value !== null)
                    .flatMap(source => source.ancestors))
                const nodes = new Map(sources.map(source => [source.key, {
                    ...source,
                    displayDepth: source.ancestors.filter(key => {
                        const parent = sourcesByKey.get(key)
                        return !parent.composition && !parent.branchOf && (parent.key !== '[]' || parent.arrayItems.length)
                    }).length,
                    hasChildren: parents.has(source.key),
                    hasSelectableDescendants: selectableAncestors.has(source.key),
                    children: [],
                    searchText: [group.label, group.detail ?? '', source.fieldLabel,
                        ...[source, ...source.arrayItems].flatMap(level => [level.type, ...level.titles, level.description ?? '',
                            ...level.enums.flatMap(enumeration => enumeration.values),
                            ...level.constraints.flatMap(constraint => constraint.values.map(value => value.value)),
                        ]),
                    ].join(' ').toLocaleLowerCase(),
                }]))
                const roots = []
                for (const source of nodes.values()) {
                    const parent = nodes.get(source.ancestors.at(-1))
                    if (parent) parent.children.push(source)
                    else roots.push(source)
                }
                return { ...group, sources: [...nodes.values()], nodes: roots }
            })
            this.jsonSchemaBranchKeys = new Set(this.jsonSchemaData.flatMap(group => group.sources.filter(source => source.branchOf || source.composition).map(source => source.key)))
        },
        jsonSchemaSyncSelection() {
            const keys = new Set()
            const ancestors = new Set()
            for (const group of this.jsonSchemaData) {
                for (const source of group.sources) {
                    if (source.value === null || !selected(source)) continue
                    keys.add(source.key)
                    for (const key of source.ancestors) ancestors.add(key)
                }
            }
            this.jsonSchemaSelectedKeys = keys
            this.jsonSchemaSelectedAncestors = ancestors
        },
        jsonSchemaSyncSearch() {
            if (!searchable()) this.jsonSchemaSearch = ''
            const terms = (search?.() ?? this.jsonSchemaSearch).toLocaleLowerCase().trim().split(/\s+/).filter(Boolean)
            this.jsonSchemaSearching = terms.length > 0
            if (!terms.length) {
                this.jsonSchemaMatches = this.jsonSchemaData.filter(group => group.sources.length)
                return
            }
            this.jsonSchemaMatches = this.jsonSchemaData.map(group => ({
                ...group,
                sources: group.sources.filter(source => !source.composition && terms.every(term => source.searchText.includes(term))),
            })).filter(group => group.sources.length)
        },
        jsonSchemaReset() {
            this.jsonSchemaSearch = ''
            this.jsonSchemaExpanded = {}
        },
        jsonSchemaSelected(source) { return this.jsonSchemaSelectedKeys.has(source.key) },
        jsonSchemaIsExpanded(key) {
            return this.jsonSchemaExpanded[key] ?? (key === '[]' || this.jsonSchemaBranchKeys.has(key) || this.jsonSchemaSelectedAncestors.has(key))
        },
        jsonSchemaRowExpandable(source) {
            return !source.composition && (disabled || source.compatible === false || source.value === null) && source.hasChildren && !this.jsonSchemaSearching
        },
        jsonSchemaToggle(source) { this.jsonSchemaExpanded[source.key] = !this.jsonSchemaIsExpanded(source.key) },
        jsonSchemaChoose(source) {
            if (disabled || !choose || source.compatible === false || source.value === null) return false
            return choose(source)
        },
        jsonSchemaName(source) {
            return this.jsonSchemaSearching ? source.fieldLabel : source.branchOf ? `${labels.variant} ${source.variantIndex}` : source.fieldName
        },
    }
}

export default jsonSchemaPicker

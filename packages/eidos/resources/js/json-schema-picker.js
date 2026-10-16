import { schemaPresentation } from './json-schema-presentation.js'

export function jsonSchemaPicker({ groups, selected = () => false, choose = null, search = null, searchable = () => true, disabled = false }) {
    return {
        ...schemaPresentation,
        jsonSchemaSelectedKeys: new Set(),
        jsonSchemaSelectedAncestors: new Set(),
        jsonSchemaData: [],
        jsonSchemaMatches: [],
        jsonSchemaSearch: '',
        jsonSchemaSearching: false,
        jsonSchemaExpanded: {},
        jsonSchemaSync() {
            this.jsonSchemaData = groups().map(group => {
                const hidden = new Set(group.sources.filter(source => source.arrayItem).map(source => source.key))
                const root = group.sources.find(source => source.key === '[]' && source.arrayItems.length)
                const sources = group.sources.filter(source => !source.arrayItem).map(source => ({
                    ...source,
                    ancestors: [
                        ...(root && source !== root ? [root.key] : []),
                        ...source.ancestors.filter(key => !hidden.has(key)),
                    ],
                    depth: source.depth - source.ancestors.filter(key => hidden.has(key)).length + (root && source !== root ? 1 : 0),
                }))
                const parents = new Set(sources.map(source => source.ancestors.at(-1)))
                return {
                    ...group,
                    sources: sources.map(source => ({
                        ...source,
                        hasChildren: hidden.size ? parents.has(source.key) : source.hasChildren,
                        searchText: [group.label, group.detail ?? '', source.fieldLabel, ...source.titles, source.description ?? '', source.type,
                            ...source.enums.flatMap(enumeration => enumeration.values),
                            ...source.constraints.flatMap(constraint => constraint.values.map(value => value.value)),
                            ...source.arrayItems.flatMap(item => [item.type, ...item.titles, item.description ?? '',
                                ...item.enums.flatMap(enumeration => enumeration.values),
                                ...item.constraints.flatMap(constraint => constraint.values.map(value => value.value)),
                            ]),
                        ].join(' ').toLocaleLowerCase(),
                    })),
                }
            })
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
                sources: group.sources.filter(source => terms.every(term => source.searchText.includes(term))),
            })).filter(group => group.sources.length)
        },
        jsonSchemaSelected(source) { return this.jsonSchemaSelectedKeys.has(source.key) },
        jsonSchemaIsExpanded(key) {
            return this.jsonSchemaExpanded[key] ?? (key === '[]' || this.jsonSchemaSelectedAncestors.has(key))
        },
        jsonSchemaVisible(source) {
            return !source.arrayItem && (this.jsonSchemaSearching || source.ancestors.every(key => this.jsonSchemaIsExpanded(key)))
        },
        jsonSchemaToggle(source) { this.jsonSchemaExpanded[source.key] = !this.jsonSchemaIsExpanded(source.key) },
        jsonSchemaChoose(source) {
            if (disabled || !choose || source.compatible === false) return false
            return choose(source)
        },
        jsonSchemaName(source) {
            return this.jsonSchemaSearching ? source.fieldLabel : source.fieldName
        },
    }
}

export default jsonSchemaPicker

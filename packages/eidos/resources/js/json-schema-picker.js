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
        jsonSchemaVariants: {},
        jsonSchemaSync() {
            this.jsonSchemaData = groups().map(group => {
                const byKey = new Map(group.sources.map(source => [source.key, source]))
                const controls = new Map(group.sources.filter(source => source.composition).map(source => [source.key, {
                    ...source,
                    choiceKey: JSON.stringify([group.key, source.key]),
                    variants: source.variants.map(key => byKey.get(key)),
                }]))
                const alternative = source => source.branchOf && byKey.get(source.branchOf).composition !== 'allOf'
                const hidden = new Set(group.sources.filter(source => source.arrayItem || alternative(source)).map(source => source.key))
                const root = group.sources.find(source => source.key === '[]' && source.arrayItems.length)
                const needsRoot = source => root && source !== root && !source.ancestors.includes(root.key)
                const sources = group.sources.filter(source => !source.arrayItem).map(source => ({
                    ...(controls.get(source.key) ?? source),
                    alternativeBranch: Boolean(alternative(source)),
                    selectionAncestors: source.ancestors,
                    alternatives: source.ancestors.map(key => byKey.get(key)).filter(alternative).map(branch => ({
                        control: controls.get(branch.branchOf),
                        branch: branch.key,
                    })),
                    ancestors: [
                        ...(needsRoot(source) ? [root.key] : []),
                        ...source.ancestors.filter(key => !hidden.has(key) && !controls.has(key)),
                    ],
                    depth: source.depth - source.ancestors.filter(key => hidden.has(key)).length + (needsRoot(source) ? 1 : 0),
                }))
                const parents = new Set(sources.map(source => source.ancestors.at(-1)))
                const selectableAncestors = new Set(sources
                    .filter(source => source.compatible !== false && source.value !== null)
                    .flatMap(source => source.ancestors))
                return {
                    ...group,
                    sources: sources.map(source => ({
                        ...source,
                        hasChildren: hidden.size ? parents.has(source.key) : source.hasChildren,
                        hasSelectableDescendants: selectableAncestors.has(source.key),
                        searchText: [group.label, group.detail ?? '', source.fieldLabel,
                            ...[source, ...source.arrayItems].flatMap(level => [level.type, ...level.titles, level.description ?? '',
                                ...level.enums.flatMap(enumeration => enumeration.values),
                                ...level.constraints.flatMap(constraint => constraint.values.map(value => value.value)),
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
                    for (const key of [...source.ancestors, ...source.selectionAncestors]) ancestors.add(key)
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
        jsonSchemaReset() {
            this.jsonSchemaSearch = ''
            this.jsonSchemaExpanded = {}
            this.jsonSchemaVariants = {}
        },
        jsonSchemaSelected(source) { return this.jsonSchemaSelectedKeys.has(source.key) },
        jsonSchemaIsExpanded(key) {
            return this.jsonSchemaExpanded[key] ?? (key === '[]' || this.jsonSchemaSelectedAncestors.has(key))
        },
        jsonSchemaVisible(source) {
            return !source.arrayItem && (this.jsonSchemaSearching || !source.alternativeBranch && source.ancestors.every(key => this.jsonSchemaIsExpanded(key))
                && source.alternatives.every(({ control, branch }) => this.jsonSchemaVariant(control).key === branch))
        },
        jsonSchemaRowExpandable(source) {
            return !source.composition && (disabled || source.compatible === false || source.value === null) && source.hasChildren && !this.jsonSchemaSearching
        },
        jsonSchemaVariant(source) {
            return source.variants.find(variant => variant.key === this.jsonSchemaVariants[source.choiceKey])
                ?? source.variants.find(variant => this.jsonSchemaSelectedAncestors.has(variant.key))
                ?? source.variants[0]
        },
        jsonSchemaVariantLabel(source, variant) {
            const title = variant.title || labels.variant
            return !variant.title || source.variants.filter(item => item.title === variant.title).length > 1
                ? `${title} ${variant.variantIndex} / ${source.variants.length}` : title
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

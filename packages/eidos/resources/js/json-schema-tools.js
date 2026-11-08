import { schemaGroups, schemaSelectionLabel } from './json-schema-definition.js'
import { schemaPresentation } from './json-schema-presentation.js'

export * from './json-schema-definition.js'
export { schemaPresentation } from './json-schema-presentation.js'

export default function jsonSchemaTools() {
    return {
        ...schemaPresentation,
        schemaGroups, schemaSelectionLabel, schemaSelectedKey: null,
        schemaData: [], schemaSelectionData: null,
        schemaDescriptions: true, schemaColors: true, schemaFormat: false, schemaProperties: true, schemaRequired: false,
        schemaSelectArrayItemFields: false, schemaConstraints: false, schemaCompact: false, schemaSearchable: true, schemaIcons: {}, schemaPropertyTitles: {},
        dropdownResizeObserver: null,
        dropdownMutationObserver: null,
        schemaBuildGroups(schema, label, { includeRoot = false, separateAlternatives = false } = {}) {
            const groups = schemaGroups(schema, label, {
                includeRoot, separateAlternatives, whole: label, descendArrays: true, selectArrayItemFields: this.schemaSelectArrayItemFields,
                format: this.schemaFormat, required: this.schemaRequired, constraints: this.schemaConstraints,
                icons: this.schemaIcons, colors: this.schemaColors, propertyTitles: this.schemaPropertyTitles,
            })
            if (includeRoot && schema?.type === 'object' && !schema.anyOf && !schema.oneOf && !schema.allOf) {
                for (const group of groups) {
                    if (group.sources.length > 1) group.sources.shift()
                }
            }
            return groups
        },
        get schemaSelections() {
            const values = Array.isArray(this.schemaSelectedKey) ? this.schemaSelectedKey : [this.schemaSelectedKey]
            return values.filter(value => value !== null).map(value => ({
                value, source: (this.schemaSelectionData ?? this.schemaData)[0]?.sources.find(source => source.value === value) ?? null,
            }))
        },
        get schemaSelectedSource() {
            return this.schemaSelections[0]?.source ?? null
        },
        schemaToggleSelection(values, value) {
            return (values ?? []).includes(value) ? values.filter(selected => selected !== value) : [...(values ?? []), value]
        },
        matchDropdownWidth(panel, trigger) {
            let width = `${trigger.getBoundingClientRect().width}px`
            const sync = () => {
                if (panel.style.width !== width) {
                    panel.style.width = width
                    width = panel.style.width
                }
            }
            sync()
            this.dropdownResizeObserver = new ResizeObserver(() => {
                width = `${trigger.getBoundingClientRect().width}px`
                sync()
            })
            this.dropdownResizeObserver.observe(trigger)
            this.dropdownMutationObserver = new MutationObserver(sync)
            this.dropdownMutationObserver.observe(panel, { attributes: true, attributeFilter: ['style'] })
        },
        destroy() {
            this.dropdownResizeObserver?.disconnect()
            this.dropdownMutationObserver?.disconnect()
        },
    }
}

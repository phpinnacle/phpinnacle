import { schemaGroups, schemaSelectionLabel } from './json-schema-definition.js'
import { schemaPresentation } from './json-schema-presentation.js'

export * from './json-schema-definition.js'
export { schemaPresentation } from './json-schema-presentation.js'

export default function jsonSchemaTools() {
    return {
        ...schemaPresentation,
        schemaGroups, schemaSelectionLabel, schemaSelectedKey: null,
        schemaData: [], schemaSelectedSource: null,
        schemaDescriptions: true, schemaColors: true, schemaFormat: false, schemaProperties: true, schemaRequired: false,
        schemaSelectArrayItemFields: false, schemaConstraints: false, schemaCompact: false, schemaSearchable: true, schemaIcons: {}, schemaPropertyTitles: {},
        dropdownResizeObserver: null,
        dropdownMutationObserver: null,
        schemaBuildGroups(schema, label) {
            return schemaGroups(schema, label, {
                includeRoot: false, descendArrays: true, selectArrayItemFields: this.schemaSelectArrayItemFields,
                format: this.schemaFormat, required: this.schemaRequired, constraints: this.schemaConstraints,
                icons: this.schemaIcons, colors: this.schemaColors, propertyTitles: this.schemaPropertyTitles,
            })
        },
        schemaSyncSelection() {
            this.schemaSelectedSource = this.schemaData[0]?.sources.find(source => source.value === this.schemaSelectedKey) ?? null
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

import { blankPart, compactValues, definitions, describe, isValid, parseVisual } from './cron-expression-core.js'

export { describe, isValid, parseVisual }

export default function tempoCron({ state, labels, gridColumns, showDayOfWeek = true, defaultMode = 'visual', presets = {} }) {
    return {
        state,
        labels,
        gridColumns,
        showDayOfWeek,
        defaultMode,
        presets,
        raw: '',
        mode: defaultMode,
        activePart: null,
        popupLeft: 0,
        popupAbove: false,
        preset: '',
        presetOpen: false,
        parts: definitions.map(blankPart),

        init() {
            this.syncState(this.state)
            this.$watch('state', value => {
                if (value !== this.raw) this.syncState(value)
            })
        },

        isValid,
        describe() {
            return describe(this.raw, this.labels)
        },

        syncState(value) {
            this.raw = typeof value === 'string' ? value : ''
            this.activePart = null
            this.presetOpen = false
            this.preset = this.matchingPreset(this.raw)

            if (this.raw === '') {
                this.mode = this.defaultMode
                this.parts = definitions.map(blankPart)
                return
            }

            const parts = this.parseParts(this.raw)
            if (parts) this.parts = parts
            else this.mode = 'expression'
        },

        matchingPreset(value) {
            return Object.keys(this.presets).find(key => this.presets[key] === value) ?? ''
        },

        parseParts(value) {
            if (this.showDayOfWeek) return parseVisual(value)
            if (!isValid(value)) return null

            const fields = value.trim().split(/\s+/)
            fields[4] = '*'

            return parseVisual(fields.join(' '))
        },

        visibleParts() {
            return this.showDayOfWeek ? this.parts : this.parts.slice(0, -1)
        },

        write(value) {
            this.raw = value
            this.preset = this.matchingPreset(value)
            this.state = value
        },

        presetLabel(key = this.preset) {
            return key ? this.labels.presets?.[key] ?? key : this.labels.custom
        },

        choosePreset(key = this.preset) {
            if (!key) return

            this.write(this.presets[key])
            this.syncState(this.state)
        },

        canShowVisual() {
            return this.raw === '' || this.parseParts(this.raw) !== null
        },

        showVisual() {
            const parts = this.raw === '' ? this.parts : this.parseParts(this.raw)
            if (!parts) return

            this.parts = parts
            this.mode = 'visual'
        },

        togglePart(part, trigger) {
            if (this.activePart === part.name) {
                this.activePart = null
                return
            }

            this.activePart = part.name
            this.$nextTick(() => this.positionPopup(trigger))
        },

        positionPopup(trigger = null) {
            const visual = this.$refs.visual
            const popup = visual.querySelector('.tempo-cron__fields')
            const button = trigger ?? visual.querySelector('.tempo-cron__part[aria-expanded="true"]')
            if (!button || !popup) return

            const bounds = visual.getBoundingClientRect()
            const left = button.getBoundingClientRect().left - bounds.left
            this.popupLeft = Math.max(0, Math.min(left, visual.clientWidth - popup.offsetWidth))
            this.popupAbove = window.innerHeight - bounds.bottom < popup.offsetHeight + 8
                && bounds.top > popup.offsetHeight + 8
        },

        closePart(restoreFocus = false) {
            if (restoreFocus) this.$refs.visual.querySelector('.tempo-cron__part[aria-expanded="true"]')?.focus()
            this.activePart = null
        },

        summary(part) {
            if (part.mode === 'step') return part.value === '1' ? this.labels.every : this.labels.step.replace('N', part.value)
            if (part.name === 'day' && part.values.length === 1 && part.values[0] === 'L') return this.labels.last
            return compactValues(part.values).replaceAll('-', '–')
        },

        toggleMode(part) {
            part.mode = part.mode === 'step' ? 'multiple' : 'step'
            if (part.mode === 'step') part.value = '1'
            this.updateVisual()
            this.$nextTick(() => this.positionPopup())
        },

        toggleValue(part, value) {
            if (part.name === 'day' && (value === 'L' || part.values.includes('L'))) {
                if (part.values.includes(value)) return
                part.values = [value]
                this.updateVisual()
                return
            }

            if (part.values.includes(value)) {
                if (part.values.length === 1) return
                part.values = part.values.filter(item => item !== value)
            } else {
                part.values.push(value)
                part.values.sort((left, right) => Number(left) - Number(right))
            }
            this.updateVisual()
        },

        options(part) {
            const first = part.mode === 'step' ? 1 : part.minimum
            const last = part.mode === 'step' ? part.maximum - part.minimum + 1 : part.maximum

            const options = Array.from({ length: last - first + 1 }, (_, index) => {
                const value = String(first + index)
                return { value, label: part.mode === 'step' ? value : this.valueLabel(part, value) }
            })

            if (part.name === 'day' && part.mode === 'multiple') options.push({ value: 'L', label: this.labels.last })

            return options
        },

        valueLabel(part, value) {
            const name = part.name === 'weekday' ? this.labels.weekdays?.[Number(value)]
                : part.name === 'month' ? this.labels.months?.[Number(value) - 1] : null

            return name ? `${name} (${value})` : value
        },

        gridLabel(part, value) {
            if (part.name === 'day' && value === 'L') return this.labels.last
            if (part.name === 'weekday') return `${this.labels.short_weekdays?.[Number(value)] ?? value} ${value}`
            if (part.name === 'month') return this.labels.short_months?.[Number(value) - 1] ?? value
            return value
        },

        updateVisual() {
            const fields = this.parts.map(part => {
                if (part.mode === 'step') return part.value === '1' ? '*' : `*/${part.value}`
                return compactValues(part.values)
            })

            if (!this.showDayOfWeek) fields[4] = this.raw.trim().split(/\s+/)[4] ?? '*'

            this.write(fields.join(' '))
        },

        updateRaw(value) {
            this.write(value)
        },
    }
}

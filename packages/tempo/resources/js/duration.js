import { canRepresent, formatDuration, parseDuration, splitDuration, units } from './duration-core.js'

export { formatDuration, parseDuration }

function emptyParts() {
    return Object.fromEntries(units.map(unit => [unit.name, '']))
}

export default function tempoDuration({ state, labels, storageFormat = 'seconds', enabledUnits = units.map(unit => unit.name), presets = {}, quickValues = {} }) {
    return {
        state,
        labels,
        storageFormat,
        enabledUnits,
        presets,
        quickValuesByUnit: quickValues,
        units,
        raw: '',
        mode: 'visual',
        activePart: null,
        popupLeft: 0,
        popupAbove: false,
        preset: '',
        presetOpen: false,
        parts: splitDuration(0, enabledUnits),
        lastWritten: undefined,

        init() {
            this.syncState(this.state)
            this.$watch('state', value => {
                if (value !== this.lastWritten) this.syncState(value)
            })
        },

        syncState(value) {
            this.lastWritten = undefined
            this.activePart = null
            this.presetOpen = false

            if (value === null || value === '') {
                this.raw = ''
                this.parts = this.mode === 'text' ? emptyParts() : splitDuration(0, this.enabledUnits)
                this.preset = ''
                return
            }

            const seconds = this.storageFormat === 'seconds'
                ? canRepresent(value, this.enabledUnits) ? value : null
                : parseDuration(value, this.enabledUnits)

            this.raw = this.storageFormat === 'seconds' && seconds !== null
                ? formatDuration(seconds, this.enabledUnits) : String(value)
            this.preset = this.matchingPreset(seconds)

            if (seconds !== null) this.parts = splitDuration(seconds, this.enabledUnits)
            else {
                this.parts = emptyParts()
                this.mode = 'text'
            }
        },

        matchingPreset(seconds) {
            if (seconds === null) return ''
            return Object.keys(this.presets).find(key => parseDuration(this.presets[key], this.enabledUnits) === seconds) ?? ''
        },

        presetKeys() {
            return Object.keys(this.presets).filter(key => parseDuration(this.presets[key], this.enabledUnits) !== null)
        },

        presetLabel(key = this.preset) {
            return key ? this.labels.presets?.[key] ?? key : this.labels.custom
        },

        choosePreset(key) {
            const seconds = parseDuration(this.presets[key], this.enabledUnits)
            this.writeSeconds(seconds)
            this.presetOpen = false
        },

        writeSeconds(seconds) {
            this.parts = splitDuration(seconds, this.enabledUnits)
            this.commitSeconds(seconds)
        },

        commitSeconds(seconds) {
            this.raw = formatDuration(seconds, this.enabledUnits)
            this.preset = this.matchingPreset(seconds)
            this.lastWritten = this.storageFormat === 'seconds' ? seconds : this.raw
            this.state = this.lastWritten
        },

        writeRawState(value) {
            this.raw = value
            this.preset = ''
            this.lastWritten = value === '' ? null : value
            this.state = this.lastWritten
        },

        canShowVisual() {
            return this.raw === '' || parseDuration(this.raw, this.enabledUnits) !== null
        },

        showVisual() {
            const seconds = this.raw === '' ? 0 : parseDuration(this.raw, this.enabledUnits)
            if (seconds === null) return

            this.parts = splitDuration(seconds, this.enabledUnits)
            this.mode = 'visual'
        },

        showText() {
            this.activePart = null
            const seconds = parseDuration(this.raw, this.enabledUnits)
            this.parts = seconds === null ? emptyParts() : splitDuration(seconds, this.enabledUnits)
            this.mode = 'text'
        },

        normalizeTextOnBlur(event) {
            const seconds = parseDuration(this.raw, this.enabledUnits)
            if (seconds === null) return

            this.parts = splitDuration(seconds, this.enabledUnits)
            event.target.value = this.textValue()
        },

        updateTextPart(part, value) {
            this.parts[part.name] = value
            const visible = this.visibleUnits()

            if (visible.every(unit => this.parts[unit.name] === '')) {
                this.writeRawState('')
                return
            }

            const raw = visible.map(unit => `${this.parts[unit.name] || '0'}${unit.suffix}`).join(' ')
            const seconds = parseDuration(raw, this.enabledUnits)

            if (seconds === null) {
                this.writeRawState(raw)
                return
            }

            this.commitSeconds(seconds)
        },

        clearText() {
            this.parts = emptyParts()
            this.writeRawState('')
            this.$nextTick(() => this.$refs.text.querySelector('input')?.focus())
        },

        textValue() {
            return this.visibleUnits().map(unit => `${this.parts[unit.name]}${this.displaySuffix(unit)}`).join(' ')
        },

        textSegments() {
            let start = 0

            return this.visibleUnits().map(part => {
                const end = start + String(this.parts[part.name]).length
                const segment = { part, start, end }
                start = end + this.displaySuffix(part).length + 1
                return segment
            })
        },

        textSegmentIndex(position) {
            const segments = this.textSegments()
            for (let index = 1; index < segments.length; index++) {
                if (position < segments[index].start) return index - 1
            }

            return segments.length - 1
        },

        selectTextSegment(input, index) {
            const segment = this.textSegments()[index]
            if (segment) input.setSelectionRange(segment.start, segment.end)
        },

        handleTextKeydown(event) {
            const input = event.target
            const segments = this.textSegments()
            const index = this.textSegmentIndex(input.selectionStart)
            const segment = segments[index]
            const key = event.key.toLowerCase()
            const advance = event.key === ' ' || key === segment.part.suffix || key === this.displaySuffix(segment.part).toLowerCase()
            const selected = input.selectionStart !== input.selectionEnd

            if (advance || (event.key === 'ArrowRight' && (selected || input.selectionStart >= segment.end))) {
                event.preventDefault()
                this.selectTextSegment(input, index + 1)
            } else if (event.key === 'ArrowLeft' && (selected || input.selectionStart <= segment.start)) {
                event.preventDefault()
                this.selectTextSegment(input, index - 1)
            }
        },

        editTextNumber(input, inserted, deletion = null) {
            const start = input.selectionStart
            const end = input.selectionEnd

            if (start === 0 && end === input.value.length && end > 0) {
                this.parts = emptyParts()
                if (inserted === '') this.writeRawState('')
                else this.updateTextPart(this.visibleUnits()[0], inserted)
                input.value = this.textValue()
                input.setSelectionRange(inserted.length, inserted.length)
                return
            }

            const index = this.textSegmentIndex(start)
            const segment = this.textSegments()[index]
            const value = String(this.parts[segment.part.name])
            let from = Math.max(0, Math.min(start - segment.start, value.length))
            let to = Math.max(from, Math.min(end - segment.start, value.length))

            if (deletion && start === end) {
                if (deletion === 'backward' && from > 0) from--
                else if (deletion === 'forward' && to < value.length) to++
                else return
            }

            this.updateTextPart(segment.part, value.slice(0, from) + inserted + value.slice(to))
            input.value = this.textValue()
            const cursor = this.textSegments()[index].start + from + inserted.length
            input.setSelectionRange(cursor, cursor)
        },

        handleTextBeforeInput(event) {
            if (!event.cancelable) return

            if (event.inputType.startsWith('delete')) {
                event.preventDefault()
                this.editTextNumber(event.target, '', event.inputType === 'deleteContentForward' ? 'forward' : 'backward')
            } else if (event.inputType.startsWith('insert')) {
                event.preventDefault()
                if (/^\d+$/.test(event.data ?? '')) this.editTextNumber(event.target, event.data)
            }
        },

        handleTextPaste(event) {
            event.preventDefault()
            const value = event.clipboardData.getData('text').trim()

            if (/^\d+$/.test(value)) {
                this.editTextNumber(event.target, value)
                return
            }

            let canonical = value
            for (const unit of this.visibleUnits()) canonical = canonical.replaceAll(this.displaySuffix(unit), unit.suffix)
            const seconds = parseDuration(canonical, this.enabledUnits)
            if (seconds === null) return

            this.writeSeconds(seconds)
            event.target.value = this.textValue()
            this.selectTextSegment(event.target, 0)
        },

        restoreTextValue(event) {
            event.target.value = this.textValue()
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
            const popup = visual.querySelector('.tempo-duration__fields')
            const button = trigger ?? visual.querySelector('.tempo-duration__part[aria-expanded="true"]')
            if (!button || !popup) return

            const bounds = visual.getBoundingClientRect()
            this.popupLeft = Math.max(0, Math.min(button.getBoundingClientRect().left - bounds.left, visual.clientWidth - popup.offsetWidth))
            this.popupAbove = window.innerHeight - bounds.bottom < popup.offsetHeight + 8
                && bounds.top > popup.offsetHeight + 8
        },

        closePart(restoreFocus = false) {
            if (restoreFocus) this.$refs.visual.querySelector('.tempo-duration__part[aria-expanded="true"]')?.focus()
            this.activePart = null
        },

        updatePart(part, value) {
            this.parts[part.name] = value
            let total = 0

            for (const unit of this.visibleUnits()) {
                const amount = this.parts[unit.name]
                if (amount !== '' && !/^\d+$/.test(amount)) {
                    this.writeRawState(`${amount}${unit.suffix}`)
                    return
                }
                total += Number(amount) * unit.seconds
            }

            if (!Number.isSafeInteger(total)) {
                this.writeRawState(`${value}${part.suffix}`)
                return
            }

            this.commitSeconds(total)
        },

        quickValues(part) {
            return this.quickValuesByUnit[part.name] ?? []
        },

        visibleUnits() {
            return this.units.filter(unit => this.enabledUnits.includes(unit.name))
        },

        textPlaceholder() {
            const examples = { day: '1', hour: '2', minute: '30', second: '15' }

            return this.visibleUnits().map(unit => `${examples[unit.name]}${this.displaySuffix(unit)}`).join(' ')
        },

        displaySuffix(part) {
            return this.labels.suffixes?.[part.name] ?? part.suffix
        },
    }
}

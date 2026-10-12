export function itemPicker({ state, nodes, select = false, disabled = false, multiple = false }) {
    return {
        state,
        nodes,
        select,
        disabled,
        multiple,
        open: false,
        expanded: [],
        active: null,
        panelStyle: '',
        visibleValues: [],
        overflowCount: 0,
        overflowLabels: [],
        resizeObserver: null,

        init() {
            this.revealSelection()
            const refresh = () => {
                this.revealSelection()
                this.$nextTick(() => this.fitSelection())
            }
            this.$watch('state', refresh)
            this.$watch('nodes', refresh)
            if (this.select && this.multiple) {
                this.$nextTick(() => {
                    this.resizeObserver = new ResizeObserver(() => this.fitSelection())
                    this.resizeObserver.observe(this.$refs.value)
                    this.fitSelection()
                })
            }
        },

        destroy() {
            this.resizeObserver?.disconnect()
        },

        fitSelection() {
            if (!this.select || !this.multiple) return
            const container = this.$refs.value
            const counter = this.$refs.overflow
            const items = [...container.querySelectorAll('[data-selected-value]')]
                .filter(element => this.selected(element.dataset.selectedValue))
            const gap = parseFloat(getComputedStyle(container).columnGap) || 0
            const widths = [0]
            for (const item of items) {
                widths.push(widths[widths.length - 1] + item.getBoundingClientRect().width)
            }

            let count = items.length
            if (widths[count] + gap * Math.max(0, count - 1) > container.clientWidth) {
                for (count--; count > 0; count--) {
                    counter.textContent = `+${items.length - count}`
                    if (widths[count] + gap * count + counter.getBoundingClientRect().width <= container.clientWidth) break
                }
            }
            this.visibleValues = items.slice(0, count).map(element => element.dataset.selectedValue)
            this.overflowCount = items.length - count
            this.overflowLabels = items.slice(count).map(element => element.textContent.trim())
            counter.textContent = `+${this.overflowCount}`
        },

        selected(value) {
            return this.multiple ? (this.state ?? []).includes(value) : this.state === value
        },

        selectedNodes() {
            return this.nodes.filter(node => !node.group && this.selected(node.value))
        },

        selectionText() {
            return this.multiple ? (this.state ?? []).join(', ') : this.state
        },

        revealSelection() {
            const selected = this.selectedNodes()
            this.expanded = [...new Set([...this.expanded, ...selected.flatMap(node => node.ancestors)])]
            if (!this.multiple || !this.navigable().some(node => node.key === this.active)) {
                this.active = selected[0]?.key ?? this.navigable()[0]?.key ?? null
            }
        },

        visible(node) {
            return node.ancestors.every(key => this.expanded.includes(key))
        },

        navigable() {
            return this.nodes.filter(node => !node.group && this.visible(node))
        },

        toggleBranch(key) {
            this.expanded = this.expanded.includes(key)
                ? this.expanded.filter(value => value !== key)
                : [...this.expanded, key]
            this.focus(key)
        },

        activate(key) {
            const node = this.nodes.find(node => node.key === key)
            if (this.disabled || node.disabled || node.group) return
            if (!node.selectable) {
                if (node.branch) this.toggleBranch(key)
                return
            }
            if (this.multiple) {
                const values = this.state ?? []
                this.state = this.selected(node.value)
                    ? values.filter(value => value !== node.value)
                    : [...values, node.value]
            } else {
                this.state = node.value
            }
            this.active = key
            if (this.select && !this.multiple) this.close()
        },

        focus(key) {
            this.active = key
            this.$nextTick(() => {
                const element = [...this.$refs.panel.querySelectorAll('[data-item-key]')]
                    .find(element => element.dataset.itemKey === key)
                element?.focus({ preventScroll: true })
                element?.scrollIntoView({ block: 'nearest' })
            })
        },

        tabIndex(key) {
            return (this.active ?? this.navigable()[0]?.key) === key ? 0 : -1
        },

        position() {
            if (!this.select || !this.open) return
            const rect = this.$refs.trigger.getBoundingClientRect()
            const below = window.innerHeight - rect.bottom - 12
            const above = rect.top - 12
            const flip = below < 240 && above > below
            const height = Math.max(80, Math.min(360, flip ? above : below))
            const width = Math.min(rect.width, window.innerWidth - 24)
            const left = Math.max(12, Math.min(rect.left, window.innerWidth - width - 12))
            this.panelStyle = `position:fixed;margin:0;left:${left}px;width:${width}px;max-height:${height}px;${flip ? `bottom:${window.innerHeight - rect.top + 6}px;top:auto` : `top:${rect.bottom + 6}px;bottom:auto`}`
        },

        toggle() {
            if (this.disabled) return
            if (this.open) return this.close()
            this.open = true
            this.position()
            this.$refs.panel.showPopover()
            this.focus(this.active ?? this.navigable()[0]?.key)
        },

        close() {
            this.$refs.panel.hidePopover()
            this.open = false
            this.$refs.trigger.focus({ preventScroll: true })
        },

        keydown(event, key) {
            const keys = ['ArrowDown', 'ArrowUp', 'ArrowRight', 'ArrowLeft', 'Home', 'End', 'Enter', ' ', 'Escape']
            if (!keys.includes(event.key)) return
            event.preventDefault()
            event.stopPropagation()
            const visible = this.navigable()
            const index = visible.findIndex(node => node.key === key)
            const node = visible[index]
            if (event.key === 'Escape' && this.select) return this.close()
            if (!node) return
            switch (event.key) {
                case 'ArrowDown': return this.focus(visible[Math.min(index + 1, visible.length - 1)].key)
                case 'ArrowUp': return this.focus(visible[Math.max(index - 1, 0)].key)
                case 'Home': return this.focus(visible[0].key)
                case 'End': return this.focus(visible[visible.length - 1].key)
                case 'Enter':
                case ' ': return this.activate(key)
                case 'ArrowRight':
                    if (!node.branch) return
                    if (!this.expanded.includes(key)) return this.toggleBranch(key)
                    return this.focus(visible[index + 1]?.key ?? key)
                case 'ArrowLeft':
                    if (this.expanded.includes(key)) return this.toggleBranch(key)
                    if (node.parent) return this.focus(node.parent)
            }
        },
    }
}

export default itemPicker

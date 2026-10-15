export function itemPicker({
    state,
    nodes = [],
    html = '',
    selectionNodes = null,
    selectionHtml = '',
    empty = false,
    limit = null,
    searchable = false,
    hasResolver = false,
    fetchItems = null,
    fetchSelection = null,
    select = false,
    disabled = false,
    multiple = false,
}) {
    const component = {
        state,
        nodes,
        select,
        disabled,
        multiple,
        itemsHtml: html,
        sourceHtml: null,
        selectionNodes,
        selectionHtml,
        inlineNodes: [],
        inlineHtml: selectionHtml,
        inlineSearch: null,
        limit,
        searchable,
        hasResolver,
        fetchItems,
        fetchSelection,
        search: '',
        loading: false,
        error: false,
        errorOffset: 0,
        selectionError: false,
        nextOffset: limit ?? 0,
        hasMore: limit !== null && !empty,
        requestVersion: 0,
        selectionVersion: 0,
        searchTimer: null,
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
            this.$watch('state', () => {
                refresh()
                if (this.hasResolver) this.refreshSelection()
            })
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
            clearTimeout(this.searchTimer)
            this.requestVersion++
            this.selectionVersion++
        },

        sync(data) {
            const sourceChanged = this.sourceHtml !== data.html
                || this.disabled !== data.disabled
                || this.searchable !== data.searchable
                || this.hasResolver !== data.hasResolver
                || this.limit !== data.limit
            this.sourceHtml = data.html
            this.disabled = data.disabled
            this.searchable = data.searchable
            this.hasResolver = data.hasResolver
            this.limit = data.limit
            if (!sourceChanged) {
                if (JSON.stringify(data.state) === JSON.stringify(this.state)) {
                    this.selectionVersion++
                    this.setSelection(data.selectionNodes, data.selectionHtml)
                }
                this.selectionError = false
                this.$nextTick(() => this.fitSelection())
                return
            }
            if (!this.disabled && (this.search || this.nextOffset > (this.limit ?? 0))) {
                this.requestVersion++
                this.loading = false
                this.load(0)
                return
            }
            this.requestVersion++
            this.selectionVersion++
            this.loading = false
            this.error = false
            this.selectionError = false
            this.applyResult(data, 0, JSON.stringify(data.state))
        },

        scheduleSearch() {
            clearTimeout(this.searchTimer)
            this.requestVersion++
            this.loading = false
            this.error = false
            this.hasMore = false
            this.nodes = []
            this.itemsHtml = ''
            this.searchTimer = setTimeout(() => this.load(0), 300)
        },

        async load(offset = 0) {
            if (this.disabled || this.loading) return
            const version = ++this.requestVersion
            const state = JSON.stringify(this.state)
            this.loading = true
            this.error = false
            try {
                const result = await this.fetchItems(this.search || null, offset, JSON.parse(state))
                if (version !== this.requestVersion) return
                this.applyResult(result, offset, state)
            } catch {
                if (version === this.requestVersion) {
                    this.error = true
                    this.errorOffset = offset
                }
            } finally {
                if (version === this.requestVersion) this.loading = false
            }
        },

        applyResult(result, offset, state) {
            const focused = this.$refs.panel.querySelector('[data-item-key]:focus')?.dataset.itemKey
            this.nodes = offset ? [...this.nodes, ...result.nodes] : result.nodes
            this.itemsHtml = offset ? this.itemsHtml + result.html : result.html
            this.nextOffset = offset + (this.limit ?? 0)
            this.hasMore = this.limit !== null && !result.empty
            if (state === JSON.stringify(this.state)) {
                const append = offset && !this.hasResolver
                this.setSelection(
                    append ? [...this.selectionNodes, ...result.selectionNodes] : result.selectionNodes,
                    append ? this.selectionHtml + result.selectionHtml : result.selectionHtml,
                )
            }
            if (!this.select && this.hasResolver && !offset && this.inlineSearch !== this.search) {
                this.retainInlineSelection((this.selectionNodes ?? []).filter(node => this.selected(node.value)))
            }
            if (!offset) this.expanded = []
            if (this.search) this.expanded = this.nodes.filter(node => node.branch).map(node => node.key)
            this.revealSelection()
            this.$nextTick(() => {
                this.fitSelection()
                this.position()
                if (focused) this.$refs.panel.querySelector(`[data-item-key="${focused}"]`)?.focus({ preventScroll: true })
                this.loadMore()
            })
        },

        loadMore() {
            if (!this.hasMore || this.loading || this.error || this.disabled) return
            const panel = this.$refs.panel
            if (!panel.clientHeight) return
            if (panel.scrollHeight - panel.scrollTop - panel.clientHeight <= 48) this.load(this.nextOffset)
        },

        async refreshSelection() {
            const version = ++this.selectionVersion
            const state = JSON.stringify(this.state)
            this.selectionError = false
            try {
                const result = await this.fetchSelection(JSON.parse(state))
                if (version !== this.selectionVersion || state !== JSON.stringify(this.state)) return
                this.setSelection(result.nodes, result.html)
                this.$nextTick(() => this.fitSelection())
            } catch {
                if (version === this.selectionVersion) this.selectionError = true
            }
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

        setSelection(nodes, html) {
            this.selectionNodes = nodes
            this.selectionHtml = html
        },

        cacheSelection(node) {
            if (!this.select || !this.hasResolver) return
            const nodes = this.selectionNodes ?? []
            if (nodes.some(selected => selected.value === node.value)) return
            const template = this.$refs.panel.querySelector(`[data-item-key="${node.key}"] [data-item-selection]`)
            this.setSelection([...nodes, node], this.selectionHtml + template.innerHTML)
        },

        retainInlineSelection(nodes) {
            const loaded = new Set(this.nodes.filter(node => !node.group).map(node => node.value))
            this.inlineNodes = nodes.filter(node => !loaded.has(node.value)).map(node => ({ ...node, branch: false }))
            this.inlineHtml = this.selectionHtml
            this.inlineSearch = this.search
        },

        visibleInlineNodes() {
            return this.inlineNodes.filter(node => this.inlineVisible(node.value))
        },

        inlineVisible(value) {
            return !this.select
                && this.inlineNodes.some(node => node.value === value)
                && !this.nodes.some(node => node.branch && node.value === value)
        },

        selectedNodes() {
            return (this.selectionNodes ?? this.nodes).filter(node => !node.group && this.selected(node.value))
        },

        selectionText() {
            return this.multiple ? (this.state ?? []).join(', ') : this.state
        },

        revealSelection() {
            const selected = this.displayNodes().filter(node => !node.group && this.selected(node.value))
            this.expanded = [...new Set([...this.expanded, ...selected.flatMap(node => node.ancestors)])]
            if (!this.multiple || !this.navigable().some(node => node.key === this.active)) {
                this.active = selected[0]?.key ?? this.navigable()[0]?.key ?? null
            }
        },

        visible(node) {
            return node.ancestors.every(key => this.expanded.includes(key))
        },

        navigable() {
            return this.displayNodes().filter(node => !node.group && this.visible(node))
        },

        displayNodes() {
            if (this.select) return this.nodes
            const inline = this.visibleInlineNodes()
            const retained = new Set(inline.map(node => node.value))
            return [...inline, ...this.nodes.filter(node => node.group || !retained.has(node.value))]
        },

        toggleBranch(key) {
            this.expanded = this.expanded.includes(key)
                ? this.expanded.filter(value => value !== key)
                : [...this.expanded, key]
            this.focus(key)
        },

        activate(key) {
            const node = this.displayNodes().find(node => node.key === key)
            if (this.disabled || node.disabled || node.group) return
            if (!node.selectable) {
                if (node.branch) this.toggleBranch(key)
                return
            }
            this.cacheSelection(node)
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
            this.$nextTick(() => this.loadMore())
            if (this.searchable) {
                this.$nextTick(() => this.$refs.search.focus())
            } else {
                this.focus(this.active ?? this.navigable()[0]?.key)
            }
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

    if (!select && hasResolver) component.retainInlineSelection(selectionNodes ?? [])

    return component
}

export default itemPicker

export default function fieldMapping({ element, state, disabled, inverse = false, requiredTargets = [], connectionModes, targetModes, staticDefaults = {} }) {
    let ports;
    const isStaticValue = (entry) => entry?.kind === 'value';

    return {
        state: state ?? [],
        disabled,
        inverse,
        requiredTargets,
        connectionModes,
        targetModes,
        sourceTypes: {},
        search: '',
        visibilityFilter: 'all',
        selected: null,
        connections: [],
        hoveredConnection: null,
        focusedConnection: null,
        width: 1,
        height: 1,
        announcement: '',
        observer: null,

        init() {
            this.observer = new ResizeObserver(() => this.refresh());
            this.observer.observe(this.$refs.canvas);
            element.querySelectorAll('[data-mapping-row], [data-mapping-anchor]').forEach((element) => this.observer.observe(element));
            ['state', 'search', 'visibilityFilter'].forEach((property) => {
                this.$watch(property, () => {
                    if (property !== 'state') this.cancel();
                    this.$nextTick(() => this.refresh());
                });
            });
            this.$nextTick(() => this.refresh());
        },

        destroy() {
            this.observer?.disconnect();
        },

        updateConfiguration(connectionModes, sourceIds, sourceTypes, targetModes) {
            this.connectionModes = connectionModes;
            this.targetModes = targetModes;
            this.sourceTypes = sourceTypes;
            if (this.selected?.side === 'source' && !sourceIds.includes(this.selected.id)) this.cancel();
            ports = undefined;
            this.$nextTick(() => {
                this.observer?.disconnect();
                this.observer?.observe(this.$refs.canvas);
                element.querySelectorAll('[data-mapping-row], [data-mapping-anchor]').forEach(element => this.observer?.observe(element));
                this.refresh();
            });
        },

        mappings() {
            const mappings = new Map();
            this.state.map((row, index) => ({ row, index }))
                .sort((a, b) => (a.row.position ?? a.index) - (b.row.position ?? b.index))
                .forEach(({ row }) => {
                    if (row.target === null) return;
                    const source = row.source;
                    if (row.kind === 'static') {
                        mappings.set(row.target, { kind: 'value', value: row.static });
                        return;
                    }
                    const entry = { kind: 'source', source };
                    if (this.targetMode(row.target) !== 'whole') {
                        if (!Array.isArray(mappings.get(row.target))) mappings.set(row.target, []);
                        mappings.get(row.target).push(entry);
                    } else {
                        mappings.set(row.target, entry);
                    }
                });
            return Object.fromEntries(mappings);
        },

        restoreStaticDefault(target) {
            if (Object.hasOwn(staticDefaults, target) && !this.state.some(row => row.target === target)) {
                const draft = this.state.find(row => row.kind === 'static' && row.draftTarget === target);
                const value = JSON.parse(JSON.stringify(staticDefaults[target]));
                if (draft) {
                    draft.target = target;
                    draft.static = value;
                } else {
                    this.state.push({ kind: 'static', target, static: value, options: {} });
                }
            }
        },

        isConstant(target) {
            return isStaticValue(this.mappings()[target]);
        },

        pairs() {
            return this.state.flatMap(row => {
                const source = row.source;
                return row.target !== null && row.kind === 'source' ? [{ source, target: row.target }] : [];
            });
        },

        mappedTargetCount() {
            return Object.keys(this.mappings()).length;
        },

        isMapped(side, id) {
            return side === 'target'
                ? Object.hasOwn(this.mappings(), id)
                : this.pairs().some((pair) => pair.source === id);
        },

        isSelected(side, id) {
            return this.selected?.side === side && this.selected.id === id;
        },

        isDisabled(side, id) {
            return this.disabled || side === 'source' && this.isMapped(side, id);
        },

        fieldBindings(side, id) {
            return {
                '@click': () => this.select(side, id),
                ':aria-pressed': () => this.isSelected(side, id),
                ':aria-disabled': () => this.isDisabled(side, id) || this.isIncompatible(side, id),
                ':disabled': () => this.isDisabled(side, id),
            };
        },

        rowClasses(side, id, required) {
            const mapped = this.isMapped(side, id);
            const incompatible = this.isIncompatible(side, id);

            return {
                'is-mapped': mapped,
                'is-disconnecting': !this.disabled && [this.hoveredConnection, this.focusedConnection]
                    .some(connection => connection?.[side] === id),
                'is-selected': this.isSelected(side, id),
                'is-incompatible': incompatible,
                'is-compatible': Boolean(this.selected && this.selected.side !== side && !this.isDisabled(side, id) && !incompatible),
                'is-required-missing': required && !mapped,
                'has-type-conflict': side === 'target' && this.hasTypeConflict(id),
            };
        },

        missingRequired() {
            return this.requiredTargets.filter((id) => !this.isMapped('target', id));
        },

        canConnect(source, target) {
            return Boolean(this.port('source', source) && this.port('target', target) && this.connectionModes[source]?.[target]);
        },

        targetMode(target) {
            return this.targetModes[target];
        },

        isIncompatible(side, id) {
            const origin = this.selected;
            if (!origin || origin.side === side) return false;

            const source = side === 'source' ? id : origin.id;
            const target = side === 'target' ? id : origin.id;
            return !this.canConnect(source, target) || !this.isSourceAvailable(source, target);
        },

        isSourceAvailable(source, target) {
            return !this.pairs().some((pair) => pair.target !== target && pair.source === source);
        },

        hasTypeConflict(target) {
            const pairs = this.pairs().filter(pair => pair.target === target);
            const mode = this.targetMode(target);
            return mode === 'whole' && pairs.length > 1
                || pairs.some(pair => !this.canConnect(pair.source, target) || this.connectionModes[pair.source]?.[target] !== mode);
        },

        isVisible(side, id, text) {
            const searchableText = side === 'source' ? `${text} ${this.sourceTypes[id] ?? ''}` : text;
            return searchableText.toLocaleLowerCase().includes(this.search.trim().toLocaleLowerCase())
                && (this.visibilityFilter === 'all' || this.pairs().some(pair => pair[side] === id) === (this.visibilityFilter === 'mapped'));
        },

        selectFromCard(event, side, id) {
            const control = event.target.closest('button, a, input, select, textarea, label, [role="button"], [data-field-interactive], [role="combobox"], [contenteditable]');
            if (control && event.currentTarget.contains(control)) return;
            this.select(side, id);
        },

        select(side, id) {
            if (this.isDisabled(side, id) || this.isIncompatible(side, id)) return;

            if (this.selected && this.selected.side !== side) {
                const source = side === 'source' ? id : this.selected.id;
                const target = side === 'target' ? id : this.selected.id;
                this.connect(source, target);
                return;
            }

            this.selected = this.isSelected(side, id) ? null : { side, id };
            this.announcement = this.selected
                ? `Select a ${side === 'source' ? 'destination' : 'source'} to connect.`
                : 'Selection cancelled.';
        },

        connect(source, target) {
            if (this.disabled || !this.canConnect(source, target) || !this.isSourceAvailable(source, target)) return;

            const mode = this.connectionModes[source][target];
            const multiple = mode !== 'whole';
            this.state.forEach(row => {
                if (row.target !== target) return;
                const rowSource = row.source;
                if (rowSource === source || multiple && this.connectionModes[rowSource]?.[target] === mode) return;
                if (row.kind === 'static') row.draftTarget = target;
                row.target = null;
            });
            let row = this.state.find(row => row.kind === 'source' && row.source === source);
            if (!row) {
                row = { kind: 'source', source, target: null, options: {} };
                this.state.push(row);
            }
            if (multiple && row.target !== target) {
                row.position = Math.max(this.state.length - 1, ...this.state.map(row => row.position ?? 0)) + 1;
            }
            row.target = target;

            this.selected = null;
            this.announcement = 'Fields connected.';
        },

        remove(target, source = null) {
            if (this.disabled) return;

            this.state.forEach(row => {
                if (row.target !== target) return;
                const rowSource = row.source;
                if (source !== null && rowSource !== source) return;
                if (row.kind === 'static') row.draftTarget = target;
                row.target = null;
            });
            this.restoreStaticDefault(target);
            this.announcement = 'Connection removed.';
        },

        cancel() {
            this.selected = null;
        },

        port(side, id) {
            ports ??= new Map(Array.from(element.querySelectorAll('[data-mapping-port]'), port => [
                `${port.dataset.side}:${port.dataset.id}`, port,
            ]));

            return ports.get(`${side}:${id}`);
        },

        point(port, canvas) {
            const rect = port?.querySelector('[data-mapping-anchor]').getBoundingClientRect();
            if (!rect || rect.width === 0 || rect.height === 0) return null;

            return {
                x: rect.left + rect.width / 2 - canvas.left,
                y: rect.top + rect.height / 2 - canvas.top,
            };
        },

        path(source, target) {
            const bend = Math.max(36, Math.abs(target.x - source.x) / 2);
            return `M ${source.x} ${source.y} C ${source.x + bend} ${source.y}, ${target.x - bend} ${target.y}, ${target.x} ${target.y}`;
        },

        refresh() {
            ports = undefined;
            const canvas = this.$refs.canvas.getBoundingClientRect();
            this.width = Math.max(1, canvas.width);
            this.height = Math.max(1, canvas.height);
            this.connections = this.pairs().flatMap(({ target, source }) => {
                const sourcePort = this.port('source', source);
                const targetPort = this.port('target', target);
                const start = this.point(sourcePort, canvas);
                const end = this.point(targetPort, canvas);
                return start && end ? [{
                    source,
                    target,
                    path: this.inverse ? this.path(end, start) : this.path(start, end),
                    midpoint: { x: (start.x + end.x) / 2, y: (start.y + end.y) / 2 },
                    label: `Disconnect ${sourcePort.dataset.label} from ${targetPort.dataset.label}`,
                }] : [];
            });
            for (const property of ['hoveredConnection', 'focusedConnection']) {
                const highlighted = this[property];
                if (highlighted && !this.connections.some(connection => connection.source === highlighted.source && connection.target === highlighted.target)) {
                    this[property] = null;
                }
            }
        },
    };
}

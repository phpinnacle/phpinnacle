export default function tabbedBuilder({ context, reorderAction, reorderable, vertical }) {
    return {
        active: null,
        keys: [],
        observer: null,

        init() {
            this.sync();
            this.observer = new MutationObserver(() => this.sync());
            this.observer.observe(this.$refs.metadata, { attributes: true });
        },

        sync() {
            const keys = JSON.parse(this.$refs.metadata.dataset.keys);
            const added = keys.filter(key => !this.keys.includes(key));
            if (this.keys.length && added.length) {
                this.active = added.at(-1);
            } else if (!keys.includes(this.active)) {
                const index = this.keys.indexOf(this.active);
                this.active = keys[Math.min(Math.max(index, 0), keys.length - 1)] ?? null;
            }
            this.keys = keys;
        },

        select(key, focus = false) {
            this.active = key;
            if (focus) {
                this.$nextTick(() => {
                    const button = Array.from(this.$refs.tabs.querySelectorAll('[role=tab]'))[this.keys.indexOf(key)];
                    button?.focus();
                    button?.scrollIntoView({ block: 'nearest', inline: 'nearest' });
                });
            }
        },

        keydown(event, key) {
            const previous = vertical ? 'ArrowUp' : 'ArrowLeft';
            const next = vertical ? 'ArrowDown' : 'ArrowRight';
            const offset = event.key === previous ? -1 : event.key === next ? 1 : 0;
            if (event.altKey && offset) {
                event.preventDefault();
                if (reorderable) this.move(key, offset);
                return;
            }
            if (!offset && event.key !== 'Home' && event.key !== 'End') return;
            event.preventDefault();
            const index = event.key === 'Home' ? 0 : event.key === 'End' ? this.keys.length - 1
                : (this.keys.indexOf(key) + offset + this.keys.length) % this.keys.length;
            this.select(this.keys[index], true);
        },

        move(key, offset) {
            const order = [...this.keys];
            const index = order.indexOf(key);
            const destination = index + offset;
            if (!reorderable || index < 0 || destination < 0 || destination >= order.length) return;
            order.splice(destination, 0, ...order.splice(index, 1));
            return this.save(order);
        },

        reorder(event) {
            if (!reorderable || event.oldDraggableIndex === event.newDraggableIndex) return;
            return this.save(Array.from(this.$refs.tabs.children, item => item.getAttribute('x-sortable-item')));
        },

        save(items) {
            return this.$wire.mountAction(reorderAction, { items }, context);
        },

        destroy() {
            this.observer?.disconnect();
        },
    };
}

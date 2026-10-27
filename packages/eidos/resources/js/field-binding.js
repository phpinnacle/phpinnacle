export default function fieldBinding({ state, disabled, sourceKeys, setTarget }) {
    return {
        state,
        disabled,
        search: '',
        visibilityFilter: 'all',
        openEditors: {},

        bind(key, defaultValue) {
            if (this.disabled) return;
            const index = this.state.findIndex(row => row.kind === 'source' && row.source === key);
            if (this.state[index].target === null) {
                const value = JSON.parse(JSON.stringify(defaultValue));
                setTarget(index, value);
                this.state[index].target = value;
            }
            this.openEditors[key] = true;
        },

        isEditing(key) {
            return this.openEditors[key] === true || this.isBound(key);
        },

        isBound(key) {
            return this.state.some(row => row.kind === 'source' && row.source === key && row.target !== null);
        },

        matchesFilter(bound) {
            return this.visibilityFilter === 'all' || bound === (this.visibilityFilter === 'bound');
        },

        isVisible(key, text) {
            return this.matchesFilter(this.isBound(key))
                && text.toLocaleLowerCase().includes(this.search.trim().toLocaleLowerCase());
        },

        boundCount() {
            return sourceKeys.filter(key => this.isBound(key)).length;
        },

        unbind(key) {
            if (this.disabled) return;
            delete this.openEditors[key];
            const index = this.state.findIndex(row => row.kind === 'source' && row.source === key);
            setTarget(index, null);
            this.state[index].target = null;
        },
    };
}

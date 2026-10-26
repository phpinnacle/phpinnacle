export default function fieldBinding({ state, disabled, sourceKeys }) {
    return {
        state,
        disabled,
        search: '',
        visibilityFilter: 'all',
        openEditors: {},

        edit(key) {
            if (!this.disabled) this.openEditors[key] = true;
        },

        closeEditor(key) {
            delete this.openEditors[key];
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
            const row = this.state.find(row => row.kind === 'source' && row.source === key);
            if (row) row.target = null;
        },
    };
}

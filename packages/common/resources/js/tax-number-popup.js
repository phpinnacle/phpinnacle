export default function taxNumberPopup({ lookup, unavailable }) {
    return {
        loading: false,
        lookup: null,
        message: null,
        requestVersion: 0,

        async load() {
            if (this.loading) return
            const version = ++this.requestVersion
            this.loading = true
            this.lookup = null
            this.message = null
            try {
                const result = await lookup()
                if (version !== this.requestVersion) return
                this.lookup = result.lookup
                this.message = result.message
            } catch {
                if (version === this.requestVersion) this.message = unavailable
            } finally {
                if (version === this.requestVersion) this.loading = false
            }
        },

        destroy() {
            this.requestVersion++
        },
    }
}

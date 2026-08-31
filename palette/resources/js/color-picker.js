export default function paletteColorPicker({
    state,
    format = 'hex',
    themeColors = {},
    wide = false,
} = {}) {
    return {
        state,
        format,
        themeColors,
        wide,
        customColor: '#000000',
        alpha: 1,
        popoverResizeObserver: null,

        init() {
            if (/^#[0-9a-f]{6}(?:[0-9a-f]{2})?$/i.test(this.state ?? '')) {
                this.customColor = this.state.slice(0, 7)

                if (this.state.length === 9) {
                    this.alpha = parseInt(this.state.slice(7, 9), 16) / 255
                }
            }

            if (this.wide) {
                this.$nextTick(() => {
                    this.syncPopoverWidth()
                    this.popoverResizeObserver = new ResizeObserver(() =>
                        this.syncPopoverWidth(),
                    )
                    this.popoverResizeObserver.observe(this.$root)
                })
            }
        },

        destroy() {
            this.popoverResizeObserver?.disconnect()
        },

        syncPopoverWidth() {
            this.$root.style.setProperty(
                '--phpinnacle-palette-color-picker-width',
                `${this.$root.offsetWidth}px`,
            )
        },

        previewColor() {
            return this.themeColors[this.state] || this.state || 'transparent'
        },

        selectColor(color) {
            if (/^#[0-9a-f]{6}$/i.test(color)) {
                this.customColor = color
                this.state = this.format === 'rgba' ? this.colorWithAlpha(color) : color

                return
            }

            this.state = color
        },

        selectCustomColor() {
            if (/^#[0-9a-f]{6}$/i.test(this.customColor)) {
                this.selectColor(this.customColor)
            }
        },

        colorWithAlpha(color) {
            const alpha = Math.round(this.alpha * 255)
                .toString(16)
                .padStart(2, '0')

            return `${color}${alpha}`
        },

        isSelected(color) {
            if (/^#[0-9a-f]{6}$/i.test(color)) {
                return this.state?.slice(0, 7).toLowerCase() === color.toLowerCase()
            }

            return this.state?.toLowerCase() === color.toLowerCase()
        },
    }
}

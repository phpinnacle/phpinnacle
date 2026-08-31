import IMask from 'imask'
import moment from 'moment'
import AirDatepicker from "air-datepicker";

export default function phpPicker ({
    state,
    mode,
    dateFormat,
    timeFormat,
    fullFormat,
    maskFormat,
    minDate = null,
    maxDate = null,
    autoclose = false,
    separator = ' - '
}) {
    return {
        state,
        mode,
        dateFormat,
        timeFormat,
        fullFormat,
        maskFormat,
        minDate,
        maxDate,
        autoclose,
        separator,
        mask: null,
        picker: null,

        init: function () {
            const input = this.$refs.input;

            this.minDate = this.parseDate(minDate) ?? new Date(0, 1, 1);
            this.maxDate = this.parseDate(maxDate) ?? new Date(9999, 11, 31);

            this.makeMask(input);
            this.makePicker(input);

            this.syncState(this.state)
            this.$watch('state', state => this.syncState(state))

            this.openPickerForFocusedInput(input)
        },

        syncState: function (state) {
            if (!state) {
                this.mask.value = ''
                this.picker.clear({ silent: true })
                    .then(() => this.syncMask())

                return
            }

            if (this.mode !== 'range') {
                this.mask.value = this.formatDate(state)

                return
            }

            const values = Array.isArray(state)
                ? state
                : state.split(this.separator)
            const dates = values
                .filter(Boolean)
                .map(value => value instanceof Date ? value : this.parseDate(String(value).trim()))
                .filter(Boolean)

            this.mask.value = dates
                .map(date => this.formatDate(date))
                .join(this.separator)

            this.picker.clear({ silent: true })
            this.picker.selectDate(dates, { silent: true })
                .then(() => this.syncMask())
        },

        syncMask: function () {
            this.mask.updateValue()
            this.mask.updateControl()
        },

        openPickerForFocusedInput: function (input) {
            if (document.activeElement !== input) {
                return
            }

            this.picker.show()
        },

        makeMask: function (input) {
            this.mask = IMask(input, {
                mask: this.mode !== 'range' ? Date : this.maskFormat + this.separator + this.maskFormat,
                pattern: this.mode !== 'range' ? this.maskFormat : undefined,
                format: date => this.formatDate(date),
                parse: str => this.parseDate(str),
                lazy: false,
                blocks: {
                    YYYY: {
                        mask: IMask.MaskedRange,
                        from: this.minDate.getFullYear(),
                        to: this.maxDate.getFullYear(),
                    },
                    MM: {
                        mask: IMask.MaskedRange,
                        from: 1,
                        to: 12
                    },
                    DD: {
                        mask: IMask.MaskedRange,
                        from: 1,
                        to: 31
                    },
                    HH: {
                        mask: IMask.MaskedRange,
                        from: 0,
                        to: 23
                    },
                    mm: {
                        mask: IMask.MaskedRange,
                        from: 0,
                        to: 59
                    }
                },
            });

            this.mask.on('complete', () => {
                if (this.mode === 'range') {
                    const parsed = this.parseRange(this.mask.value)

                    if (parsed.length === 0) {
                        return
                    }

                    this.state = this.mask.value

                    this.picker.selectDate(parsed, {
                        silent: true,
                    })

                    return
                }

                const parsed = this.parseDate(this.mask.value);

                if (!parsed) {
                    return
                }

                this.state = this.mask.value;

                this.picker.setViewDate(parsed);
                this.picker.selectDate(parsed, { silent: true, updateTime: true });
            });
        },

        makePicker: function (input) {
            this.picker = new AirDatepicker(input, {
                autoClose: this.autoclose,
                timepicker: this.mode === 'datetime',
                onlyTimepicker: this.mode === 'time',
                range: this.mode === 'range',
                minDate: this.minDate,
                maxDate: this.maxDate,
                dateFormat: this.toAirFormat(this.dateFormat),
                timeFormat: this.toAirFormat(this.timeFormat),
                multipleDatesSeparator: this.separator,
                onShow: (isFinished) => {
                    if (isFinished) {
                        return;
                    }

                    this.picker.$datepicker.addEventListener('mousedown', e => {
                        e.stopPropagation()
                    });

                    this.picker.$datepicker.addEventListener('click', e => {
                        e.stopPropagation()
                    });
                },
                onSelect: ({ date }) => {
                    if (this.mode === 'range') {
                        if (!Array.isArray(date) || date.length !== 2) {
                            return;
                        }

                        this.mask.value = date
                            .filter(Boolean)
                            .map(d => this.formatDate(d))
                            .join(this.separator);

                        return;
                    }

                    this.mask.typedValue = date;
                },
            });
        },

        formatDate: function (date) {
            return moment(date).format(this.maskFormat) || '';
        },

        parseDate: function (value) {
            const parsed = moment(value, this.maskFormat, true)

            return parsed.isValid() ? parsed.toDate() : null
        },

        parseRange(value) {
            return value
                .split(this.separator)
                .map(v => this.parseDate(v.trim()))
                .filter(Boolean)
        },

        toAirFormat: function (format) {
            return format
                .replace('YYYY', 'yyyy')
                .replace('MM', 'MM')
                .replace('DD', 'dd')
                .replace('HH', 'HH')
                .replace('mm', 'mm')
        },
    }
}

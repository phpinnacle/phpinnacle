export const definitions = [
    { name: 'minute', minimum: 0, maximum: 59 },
    { name: 'hour', minimum: 0, maximum: 23 },
    { name: 'day', minimum: 1, maximum: 31 },
    { name: 'month', minimum: 1, maximum: 12 },
    { name: 'weekday', minimum: 0, maximum: 7 },
]

const monthNames = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC']
const weekdayNames = ['SUN', 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT']

export function blankPart(definition) {
    const first = String(definition.minimum)

    return { ...definition, mode: 'step', value: '1', values: [first] }
}

function fieldNumber(value, index) {
    if (/^\d+$/.test(value)) return Number(value)

    const names = index === 3 ? monthNames : index === 4 ? weekdayNames : []
    const position = names.indexOf(value.toUpperCase())
    return position < 0 ? null : index === 3 ? position + 1 : position
}

function validSegment(segment, definition, index) {
    const match = segment.match(/^(\*|\d+|[a-z]{3})(?:-(\d+|[a-z]{3}))?(?:\/(\d+))?$/i)

    if (!match) return false

    const [, start, end, step] = match
    const inRange = value => {
        const number = fieldNumber(value, index)
        return number !== null && (/^\d+$/.test(value) ? value.length <= 2 : value.length === 3)
            && number >= definition.minimum && number <= definition.maximum
    }

    if (start === '*') {
        if (end !== undefined) return false
    } else if (!inRange(start)) {
        return false
    }

    if (end !== undefined && (!inRange(end) || fieldNumber(end, index) < fieldNumber(start, index))) return false

    return step === undefined || (
        (start === '*' || end !== undefined) && /^[1-9]\d*$/.test(step)
    )
}

export function isValid(expression) {
    if (typeof expression !== 'string') return false

    const fields = expression.trim().split(/\s+/)

    return fields.length === 5
        && !(fields[2] === '?' && fields[4] === '?')
        && fields.every((field, index) => field === 'L' && index === 2
            || field === '?' && (index === 2 || index === 4)
            || field.split(',').every(segment => validSegment(segment, definitions[index], index)))
}

function numericToken(token, index) {
    if (token === '?') return '*'

    return token.replace(/[a-z]{3}/gi, value => String(fieldNumber(value, index)))
}

function selectedValues(token) {
    const values = new Set()

    for (const segment of token.split(',')) {
        const match = segment.match(/^(\d+)(?:-(\d+)(?:\/(\d+))?)?$/)
        if (!match || match.slice(1).filter(Boolean).some(value => String(Number(value)) !== value)) return null

        const [, start, end = start, interval = '1'] = match
        if (Number(interval) > Number(end) - Number(start) + 1) return null

        for (let value = Number(start); value <= Number(end); value += Number(interval)) {
            if (values.has(value)) return null
            values.add(value)
        }
    }

    return [...values].sort((left, right) => left - right).map(String)
}

export function compactValues(values) {
    if (values.length === 1 && values[0] === 'L') return 'L'

    const segments = []
    const numbers = values.map(Number).sort((left, right) => left - right)

    for (let start = 0; start < numbers.length;) {
        const interval = numbers[start + 1] - numbers[start]
        let end = start + 1

        while (end < numbers.length && numbers[end] - numbers[end - 1] === interval) end++

        if (interval === 1 || end - start >= 3) {
            segments.push(`${numbers[start]}-${numbers[end - 1]}${interval === 1 ? '' : `/${interval}`}`)
            start = end
        } else {
            segments.push(String(numbers[start]))
            start++
        }
    }

    return segments.join(',')
}

export function parseVisual(expression) {
    if (!isValid(expression)) return null

    const tokens = expression.trim().split(/\s+/)
    const parts = tokens.map((token, index) => {
        const definition = definitions[index]
        token = numericToken(token, index)

        const part = blankPart(definition)

        if (token === '*') return part
        if (token === 'L') return { ...part, mode: 'multiple', values: ['L'] }
        if (/^\*\/[1-9]\d*$/.test(token)) {
            const value = token.slice(2)
            if (Number(value) <= definition.maximum - definition.minimum + 1) {
                return { ...part, mode: 'step', value }
            }
        }
        const values = selectedValues(token)
        return values ? { ...part, mode: 'multiple', values } : null
    })

    return parts.includes(null) ? null : parts
}

function fieldValues(token, index) {
    if (token === 'L' && index === 2) return []

    const { minimum, maximum } = definitions[index]
    const values = new Set()

    for (const segment of numericToken(token, index).split(',')) {
        const [, start, end, interval] = segment.match(/^(\*|\d+)(?:-(\d+))?(?:\/(\d+))?$/)
        const first = start === '*' ? minimum : Number(start)
        const last = start === '*' ? maximum : end === undefined ? first : Number(end)

        for (let value = first; value <= last; value += Number(interval ?? 1)) {
            values.add(index === 4 && value === 7 ? 0 : value)
        }
    }

    return [...values].sort((left, right) => left - right)
}

function list(values, locale) {
    return new Intl.ListFormat(locale, { style: 'long', type: 'conjunction' }).format(values.map(String))
}

function namedList(values, names, locale) {
    const ranges = []

    for (let index = 0; index < values.length;) {
        let end = index
        while (end + 1 < values.length && values[end + 1] === values[end] + 1) end++
        ranges.push(end > index + 1 ? `${names[values[index]]}–${names[values[end]]}`
            : end === index + 1 ? `${names[values[index]]}, ${names[values[end]]}` : names[values[index]])
        index = end + 1
    }

    return list(ranges, locale)
}

export function describe(expression, labels) {
    if (!isValid(expression)) return ''

    const [minuteToken, hourToken, dayToken, monthToken, weekdayToken] = expression.trim().split(/\s+/)
    const [minutes, hours, days, months, weekdays] = [minuteToken, hourToken, dayToken, monthToken, weekdayToken]
        .map((token, index) => fieldValues(token, index))
    const locale = labels.locale ?? 'en'
    const phrases = labels.description
    const format = (template, replacements) => Object.entries(replacements).reduce(
        (result, [key, value]) => result.replace(`{${key}}`, value), template,
    )
    let time

    if (minutes.length === 60 && hours.length === 24) time = phrases.every_minute
    else if (/^\*\/[1-9]\d*$/.test(minuteToken) && hourToken === '*' && Number(minuteToken.slice(2)) <= 59) {
        time = format(phrases.every_n_minutes, { value: minuteToken.slice(2) })
    } else if (minutes.length === 1 && minutes[0] === 0 && hours.length === 24) time = phrases.every_hour
    else if (minutes.length === 1 && hours.length === 1) {
        time = format(phrases.at_time, { value: `${String(hours[0]).padStart(2, '0')}:${String(minutes[0]).padStart(2, '0')}` })
    } else if (hours.length === 24) {
        const key = minutes.length === 1 ? 'at_minute_every_hour' : 'minutes_every_hour'
        time = format(phrases[key], { value: list(minutes, locale) })
    } else if (minutes.length === 60) {
        time = format(phrases.every_minute_at_hours, { value: list(hours, locale) })
    } else {
        time = format(phrases.minutes_at_hours, { minutes: list(minutes, locale), hours: list(hours, locale) })
    }

    let day = dayToken === 'L' ? phrases.last_day : days.length === 31 ? '' : format(phrases[days.length === 1 ? 'day' : 'days'], {
        value: list(days, locale),
    })
    let weekday = weekdays.length === 7 ? '' : weekdays.join(',') === '1,2,3,4,5' ? phrases.weekdays
        : format(phrases.weekdays_list, { value: namedList(weekdays, labels.short_weekdays, locale) })

    if (day && weekday) {
        day = format(phrases.day_or_weekday, { day, weekday })
        weekday = ''
    }

    const month = months.length === 12 ? '' : format(phrases.months, {
        value: namedList(months, [null, ...labels.months], locale),
    })

    return [time, day, weekday, month].filter(Boolean).join(' ') + '.'
}

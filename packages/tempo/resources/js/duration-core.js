export const units = [
    { name: 'day', suffix: 'd', seconds: 86400 },
    { name: 'hour', suffix: 'h', seconds: 3600 },
    { name: 'minute', suffix: 'm', seconds: 60 },
    { name: 'second', suffix: 's', seconds: 1 },
]

export function parseDuration(value, enabledUnits = units.map(unit => unit.name)) {
    if (typeof value !== 'string' || !/^(?:(?:0|[1-9]\d*)[dhms]\s*)+$/i.test(value.trim())) return null

    let total = 0
    for (const [, amount, suffix] of value.matchAll(/(\d+)([dhms])/gi)) {
        const unit = units.find(item => item.suffix === suffix.toLowerCase())
        if (!enabledUnits.includes(unit.name)) return null
        total += Number(amount) * unit.seconds
        if (!Number.isSafeInteger(total)) return null
    }

    return total
}

export function canRepresent(seconds, enabledUnits) {
    const smallest = Math.min(...units.filter(unit => enabledUnits.includes(unit.name)).map(unit => unit.seconds))

    return Number.isSafeInteger(seconds) && seconds >= 0 && seconds % smallest === 0
}

export function formatDuration(seconds, enabledUnits = units.map(unit => unit.name)) {
    if (!Number.isSafeInteger(seconds) || seconds < 0) return ''
    if (!canRepresent(seconds, enabledUnits)) return ''
    const activeUnits = units.filter(unit => enabledUnits.includes(unit.name))
    if (seconds === 0) return `0${activeUnits[activeUnits.length - 1].suffix}`

    const parts = []
    for (const unit of activeUnits) {
        const amount = Math.floor(seconds / unit.seconds)
        if (amount > 0) parts.push(`${amount}${unit.suffix}`)
        seconds %= unit.seconds
    }

    return parts.join(' ')
}

export function splitDuration(seconds, enabledUnits = units.map(unit => unit.name)) {
    const parts = Object.fromEntries(units.map(unit => [unit.name, '0']))
    for (const unit of units.filter(item => enabledUnits.includes(item.name))) {
        parts[unit.name] = String(Math.floor(seconds / unit.seconds))
        seconds %= unit.seconds
    }

    return parts
}

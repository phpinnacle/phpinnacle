export function jsonPath(segments) {
    return '$' + segments.map(segment => segment === null ? '[*]' : Number.isInteger(segment) ? '[' + segment + ']'
        : "['" + JSON.stringify(String(segment)).slice(1, -1).replace(/\\"/g, '"').replace(/'/g, "\\'") + "']").join('')
}

export function parseJsonPath(path) {
    if (typeof path !== 'string' || !path.startsWith('$')) throw new Error('A JSONPath must start with $.')
    const segments = []
    let offset = 1
    while (offset < path.length) {
        const token = /^\[(?:'((?:[^'\\]|\\.)*)'|(0|[1-9][0-9]*)|(\*))\]/s.exec(path.slice(offset))
        if (!token) throw new Error('Use bracketed property names, nonnegative indexes, or [*] in a JSONPath.')
        segments.push(token[1] !== undefined
            ? JSON.parse('"' + token[1].replace(/\\.|"/gs, part => part === "\\'" ? "'" : part === '"' ? '\\"' : part) + '"')
            : token[2] !== undefined ? Number(token[2]) : null)
        offset += token[0].length
    }
    if (jsonPath(segments) !== path) throw new Error('Use the canonical bracket notation for JSONPath.')
    return segments
}

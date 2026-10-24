import assert from 'node:assert/strict'
import { test } from 'node:test'
import { jsonPath, parseJsonPath } from '../resources/js/json-path.js'

test('round trips canonical JSONPath names, indexes, and wildcards', () => {
    const segments = ['literal.key', 'slash/key', '*', `quote'"\\`, 'line\n\t', 'товар', '0', 0, null]
    const path = jsonPath(segments)

    assert.ok(path.startsWith("$['literal.key']['slash/key']['*']"))
    assert.ok(path.endsWith("['0'][0][*]"))
    assert.deepEqual(parseJsonPath(path), segments)
    assert.deepEqual(parseJsonPath('$'), [])
})

test('rejects malformed and noncanonical JSONPath strings', () => {
    for (const invalid of [[], '', 'input.name', '$.name', '$["name"]', '$[-1]', '$[01]',
        '$[999999999999999999999999]', "$['bad\\q']", "$['unclosed'", "$['\\u0061']"]) {
        assert.throws(() => parseJsonPath(invalid))
    }
})

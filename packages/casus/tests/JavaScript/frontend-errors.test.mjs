import assert from 'node:assert/strict';
import test from 'node:test';

let moduleId = 0;
const initialTime = Date.UTC(2026, 0, 1, 12);

function response(status, retryAfter = null) {
    return new Response(null, {
        status,
        headers: retryAfter === null ? {} : { 'Retry-After': retryAfter },
    });
}

async function reporter(t, responses = []) {
    let now = initialTime;
    const requests = [];

    globalThis.document = { querySelector: () => ({ content: 'test-csrf' }) };
    globalThis.window = { location: { pathname: '/orders/42' } };
    t.after(() => {
        delete globalThis.document;
        delete globalThis.window;
    });
    t.mock.method(Date, 'now', () => now);
    t.mock.method(globalThis, 'fetch', async (url, options) => {
        requests.push({ url, options });
        return responses.shift() ?? response(204);
    });

    const url = new URL('../../resources/js/frontend-errors.js', import.meta.url);
    url.searchParams.set('test', String(moduleId++));
    const { reportBrowserError } = await import(url.href);

    return {
        report: reportBrowserError,
        requests,
        setTime: (value) => { now = value; },
    };
}

test('sends browser error metadata and the page CSRF token', async (t) => {
    const client = await reporter(t);
    const error = new TypeError('Failed');

    await client.report(error, { file: '/app.js', line: 12, column: 7 });

    assert.equal(client.requests.length, 1);
    const { url, options } = client.requests[0];
    assert.equal(url, '/casus/errors');
    assert.equal(options.method, 'POST');
    assert.equal(options.credentials, 'same-origin');
    assert.equal(options.headers['X-CSRF-TOKEN'], 'test-csrf');
    assert.deepEqual(JSON.parse(options.body), {
        type: 'TypeError',
        message: 'Failed',
        file: '/app.js',
        line: 12,
        column: 7,
        trace: error.stack,
        path: '/orders/42',
    });
});

test('discards reports during a seconds-based pause and sends new errors after expiry', async (t) => {
    const client = await reporter(t, [response(429, '20')]);

    await client.report(new Error('Rejected'));
    await client.report(new Error('Discarded'));
    client.setTime(initialTime + 19_999);
    await client.report(new Error('Still discarded'));
    assert.equal(client.requests.length, 1);

    client.setTime(initialTime + 20_000);
    await client.report(new Error('New error'));
    assert.equal(client.requests.length, 2);
    assert.equal(JSON.parse(client.requests[1].options.body).message, 'New error');
});

test('honors an HTTP-date Retry-After header', async (t) => {
    const retryAfter = new Date(initialTime + 3_000).toUTCString();
    const client = await reporter(t, [response(429, retryAfter)]);

    await client.report(new Error('Rejected'));
    client.setTime(initialTime + 2_999);
    await client.report(new Error('Discarded'));
    assert.equal(client.requests.length, 1);

    client.setTime(initialTime + 3_000);
    await client.report(new Error('New error'));
    assert.equal(client.requests.length, 2);
});

for (const retryAfter of [null, '', 'invalid', '-1']) {
    test(`uses a 60-second pause for an unusable Retry-After header: ${JSON.stringify(retryAfter)}`, async (t) => {
        const client = await reporter(t, [response(429, retryAfter)]);

        await client.report(new Error('Rejected'));
        client.setTime(initialTime + 59_999);
        await client.report(new Error('Discarded'));
        assert.equal(client.requests.length, 1);

        client.setTime(initialTime + 60_000);
        await client.report(new Error('New error'));
        assert.equal(client.requests.length, 2);
    });
}

test('does not shorten a pause when another in-flight request returns a shorter delay', async (t) => {
    let resolveFirst;
    const first = new Promise((resolve) => { resolveFirst = resolve; });
    const client = await reporter(t, [first, response(429, '60')]);

    const pending = client.report(new Error('First request'));
    await client.report(new Error('Second request'));
    client.setTime(initialTime + 1_000);
    resolveFirst(response(429, '5'));
    await pending;

    client.setTime(initialTime + 10_000);
    await client.report(new Error('Discarded'));
    assert.equal(client.requests.length, 2);

    client.setTime(initialTime + 60_000);
    await client.report(new Error('New error'));
    assert.equal(client.requests.length, 3);
});

test('does not pause after other response statuses', async (t) => {
    const client = await reporter(t, [response(422, '60'), response(500, '60')]);

    await client.report(new Error('First error'));
    await client.report(new Error('Second error'));
    await client.report(new Error('Third error'));

    assert.equal(client.requests.length, 3);
});

test('allows new reports immediately when Retry-After is zero', async (t) => {
    const client = await reporter(t, [response(429, '0')]);

    await client.report(new Error('Rejected'));
    await client.report(new Error('New error'));

    assert.equal(client.requests.length, 2);
});

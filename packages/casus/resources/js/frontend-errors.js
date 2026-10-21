let resumeAt = 0;

export async function reportBrowserError(error, location = {}) {
    if (Date.now() < resumeAt) {
        return;
    }

    const response = await fetch('/casus/errors', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({
            type: error.name || 'Error',
            message: error.message || String(error),
            file: location.file || '',
            line: location.line || 0,
            column: location.column || 0,
            trace: error.stack || '',
            path: window.location.pathname,
        }),
    });

    if (response.status !== 429) {
        return;
    }

    resumeAt = Math.max(resumeAt, retryDeadline(response.headers.get('Retry-After'), Date.now()));
}

function retryDeadline(value, now) {
    const fallback = now + 60_000;

    if (value === null || value.trim() === '') {
        return fallback;
    }

    const seconds = Number(value);

    if (Number.isFinite(seconds) && seconds < 0) {
        return fallback;
    }

    const deadline = Number.isFinite(seconds) ? now + seconds * 1000 : Date.parse(value);

    return Number.isFinite(deadline) ? deadline : fallback;
}

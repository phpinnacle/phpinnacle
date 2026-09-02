# Ideas

Only small, additive features are listed here. Refactors and package-wide redesigns are intentionally excluded.

## 1. Sensitive-data redaction

Redact configurable header, cookie, query, and body keys before an exception is persisted, with safe defaults for passwords, tokens, and authorization headers.

## 2. Triage status

Let operators mark an exception as open, acknowledged, or resolved and attach a short internal note, making the existing browser useful as a lightweight incident queue.

## 3. New-error notifications

Send an optional Laravel notification only for a new exception fingerprint or when its occurrence count crosses a configured threshold.

# Refactor

Only local, behavior-preserving cleanup is listed here. Public API changes and package-wide redesigns are intentionally excluded.

## 1. Move request context out of the model

Have `Recorder` pass the user identifier and normalized URL into `Recent` instead of letting the Eloquent model read the `Auth` and `Request` facades itself.

## 2. Share current-user query constraints

Use one `forUser()` query scope for listing and record lookup so user ownership is explicit wherever recent entries are read or updated.

## 3. Scope the mutable recorder

Replace the singleton binding for `Recorder` with a scoped binding, or make its page map immutable after plugin boot, so tests and Octane requests cannot retain accidental runtime registrations.

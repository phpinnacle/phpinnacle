# Ideas

Only small, additive features are listed here. Refactors and package-wide redesigns are intentionally excluded.

## 1. Lazy pagination

Provide an iterator that follows LangLion pagination links and yields DTOs across pages while retaining the existing single-page methods.

## 2. Refreshable authenticated session

Allow a client to refresh an expired access token once and report the replacement token through a caller-provided callback, without adding framework-specific storage.

## 3. Raw request escape hatch

Expose a guarded raw request method using the existing transport and error handling so newly released LangLion endpoints remain accessible before a typed wrapper is added.

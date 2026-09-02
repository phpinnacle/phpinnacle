# Refactor

Only local, behavior-preserving cleanup is listed here. Public API changes and package-wide redesigns are intentionally excluded.

## 1. Unify JSON decoding

Make `Transport::decode()` the single JSON decoding path for successful and error responses, with a non-throwing mode for API error bodies, instead of maintaining two nearly identical `json_decode()` branches.

## 2. Isolate pagination URL validation

Move relative-link normalization and same-origin validation out of `Transport::nextPage()` into the existing link/URI support area so the transport method only advances pagination.

## 3. Split the client test by boundary

Break the large `ClientTest` into focused transport-security, query-encoding, context, and response-mapping tests while retaining the same assertions and fixtures.

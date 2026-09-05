# Refactor plan

Reviewed against the working tree on 2026-09-05. Keep the PSR-only dependencies and the public client, context, exception, and lazy pagination contracts.

## 1. Priority: high — characterize the transport boundary

`tests/Unit/ClientTest.php` covers a cross-origin next link and a JSON API error, but not the full boundary implemented by `Support/Transport.php`.

- Add focused cases for allowed root-relative links, scheme/port/API-path mismatches, invalid success JSON, scalar JSON, empty responses, and non-JSON HTTP errors. Assert exception status/body and that a rejected next link never causes a second authenticated request.
- Verify lazy iteration and independent tokens after `withAccessToken()`.
- Keep this as the prerequisite to transport changes; any newly exposed URI acceptance defect is a separate behavior fix, not part of an extraction.

## 2. Priority: medium — separate pagination URI policy if it simplifies transport

`nextPage()` combines Link-header lookup, root-relative resolution, and validation against the configured base. A small internal URI resolver may own the latter two operations and the base URI state. Keep `LinkHeader` responsible for header parsing; it currently has no origin state. Leave HTTP status/body enrichment in `Transport`.

Acceptance: the boundary cases above keep the same results, errors, request count, and timing. Do not introduce a generic URI framework or share internals with another client package.

## Removed from the active queue

- A boolean mode on `decode()`: success decoding intentionally throws and treats an empty body as `[]`; error decoding intentionally returns `null` when decoding fails so `ApiException` retains the HTTP error. Two short methods make this distinction clearer.
- Splitting every client test by category: move tests only while adding boundary coverage; file size alone is not a useful deliverable.

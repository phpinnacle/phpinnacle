# Refactor plan

Reviewed against the working tree on 2026-09-05. Preserve the PSR-only package boundary, fluent request APIs, payload encoding, and typed responses.

## 1. Priority: medium — consolidate equivalent request checks

Older builders such as `ListStudentsRequest` and `ListDirectoryRequest` still implement ID and pagination checks directly; `ListGroupsRequest` uses `RequestValue::positive()` but separately enforces the per-page maximum.

- Reuse `positive()` only for equivalent positive-value contracts. It does not enforce the `perPage <= 100` bound, so replacing the full range check with it would weaken validation.
- Consolidate the repeated pagination range only with a helper that retains both bounds. Keep endpoint-specific sort, date, and required-field rules in their owning requests.
- Preserve existing exception messages, or explicitly identify a message change; `Page must be at least 1.` and the helper's `Page must be positive.` are currently different.

Acceptance: cover page zero/one, per-page zero/one/100/101, and positive IDs at request boundaries. Preserve `false`, zero, and omitted fields in the serialized query/body.

## 2. Priority: medium — reuse collection hydration without changing accepted shapes silently

`StudentsResponse` and `GroupsResponse` duplicate object-list mapping already available through `ResponseValue::objects()`.

Use that helper for equivalent collection contracts and keep endpoint-specific response DTOs. Review exception text during conversion. `ResponseValue::object()` rejects non-empty list arrays, while existing manual `is_array()` checks may accept them; changing metadata decoding is therefore a separate external-boundary validation change.

Acceptance: valid empty/populated collections retain their DTO types and pagination values; malformed external items fail at hydration with the intended exception. Add missing boundary cases instead of only moving assertions.

## Supporting cleanup

Split `tests/Unit/ClientTest.php` by API area as those areas change, sharing the recording HTTP fixture. Keep transport failures and token-copy behavior independently testable. Do not introduce a generic endpoint/DTO framework or a dependency on Canvio to remove superficial similarity.

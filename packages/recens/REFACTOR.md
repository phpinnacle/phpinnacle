# Refactor plan

Reviewed against the working tree on 2026-09-05. Per-user history correctness takes priority over moving request facades or introducing query helpers.

## 1. Priority: high — include ownership in recent-entry identity

`Recent::record()` derives its UUID only from the query-free URL and looks up by that ID. It then overwrites `user_id`. Two users visiting the same URL therefore target the same row even though `list()` filters by the current user.

- Add a database regression case with two authenticated users visiting the same URL. Repeated visits by one user should update that user's timestamp without replacing the other's entry.
- Make identity and lookup include the user, and apply the same ownership predicate to reads and writes. A `forUser()` model scope is useful as part of this fix, not as an isolated abstraction.
- Keep current URL canonicalization, ordering, limits, and pruning behavior. Specify whether enabled tenancy also forms part of identity before changing stored keys.

Acceptance: both users retain independent histories. This changes persisted identity: choose an explicit migration or pruning strategy for existing URL-only IDs and document it in the README when implemented. Do not add a permanent runtime fallback that can reclaim another user's row.

## 2. Priority: medium — align recorder lifetime with panel registrations

`Recorder` is a singleton populated from `RecensPlugin::get()` on first resolution. Its page map contains callbacks, not visit records, so mutability alone is not evidence of user-state leakage. The concrete risk is reuse of one panel's registrations for another panel/request.

Test two panels with different callbacks for the same page and a second request in the same application. Use a scoped recorder or an explicit panel-owned map only if this reproduces a mismatch, and ensure registration is reapplied after scope reset. Keep trusted callback semantics intact.

## Deferred

Passing actor and URL explicitly may help the identity tests, but replacing public `Recent::record(array)` outright would break callers. Keep a compatible entry point if introducing an explicit internal operation. Persistence and current-user model queries remain on `Recent`; a persistence service is unnecessary.

Current `tests/Unit/RecentTest.php` only checks navigation metadata. Recording, isolation, and pruning tests are required before these changes are considered complete.

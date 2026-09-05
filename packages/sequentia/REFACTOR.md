# Refactor plan

Reviewed against the working tree on 2026-09-05. Keep public static entry points, stored counter hashes, tenant/bucket isolation, and PostgreSQL atomic increments compatible.

## 1. Priority: high — define a repeatable rebuild

`SequenceWatcher::rebuild()` iterates existing records and calls the same incrementing `store()` used for newly created records. It does not reset or replace existing counts, so a second rebuild increases them again. Multiple registered bucket schemes also each increment the same global counters.

- Reproduce rebuilding twice, rebuilding after normal model creation, and registering two schemes for one scope. Cover year/month and tenant boundaries.
- Separate aggregate reconstruction from incremental storage. Define how a rebuild coordinates with concurrent creates and limits replacement to the intended registered counters; do not simply truncate the table.
- Decide and document the meaning of global counters when multiple schemes are registered, then fix any overcount explicitly.

Acceptance: rebuilding unchanged data twice gives the same next values, unrelated tenants/scopes are preserved, and concurrent writes follow a documented consistency policy. This is a behavior fix, not an extraction.

## 2. Priority: medium — isolate storage after its contracts are covered

Move hashing, reads, and atomic writes to a small package-local database adapter if that gives rebuild and event processing one coherent storage implementation. Keep model watching in `SequenceWatcher` and compatible public delegators.

Acceptance: preserve byte-for-byte hash inputs, configured connection, six counter keys, and `load()` returning stored values plus one. Existing `SequenceWatcherTest` uses SQLite; add PostgreSQL verification for database-specific behavior and concurrency. `Sequence::get()` reads a prospective number; atomic increments do not make that read a number reservation. Do not add reservation semantics during refactoring.

## 3. Priority: medium, conditional — fix registration lifetime together with listeners

`registered` contains developer definitions; `watched` tracks Eloquent listeners. Test an application/event-dispatcher reset in the same PHP process and repeated registration before replacing static arrays. A fresh scoped registry combined with old model listeners can lose registrations or double-count events.

If a lifetime defect is reproduced, make registry lifetime and listener attachment agree while preserving `register()` and boot-time declarations. Do not claim static configuration alone leaks tenant state.

## Deferred

The short `PatternFormatter` modifier closure is already isolated from persistence and has a test. Extract a private modifier method only while changing that grammar; preserve unknown-placeholder, case, Unicode slicing, and modifier-order behavior.

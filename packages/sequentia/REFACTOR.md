# Refactor status

Completed on 2026-09-06 for 1.1.1.

## Repeatable rebuild

- Aggregate reconstruction is separate from incremental writes. Rebuild atomically replaces only counter keys represented by registered model queries; unrelated and absent keys remain unchanged.
- Rebuild requires paused and drained writers, including direct stores and other rebuilds. It does not coordinate concurrent source-model writes. The README documents this maintenance policy and the treatment of deleted records and removed schemes.
- Global counters count each record once per tenant and scope across schemes. Repeated rebuilds, normal creation, multiple schemes, tenant/year/month boundaries, and transaction rollback are covered.

## Counter storage

- `CounterStore` owns hashing, reads, atomic increments, and aggregate replacement; `SequenceWatcher` retains model watching and public static entry points.
- Existing serialized hashes, configured connections, all six counter keys, and stored-value-plus-one reads remain compatible. `Sequence::get()` does not reserve numbers.
- The suite runs on SQLite and opt-in PostgreSQL, including four concurrent PostgreSQL writers.

## Registration lifetime

- Reproduced missing listeners after replacing the event dispatcher in the same PHP process.
- Registrations and listener attachment now share the dispatcher's lifetime through a weak map. Repeated registration does not duplicate listeners; new dispatchers receive fresh registrations.

## Deferred

The short `PatternFormatter` modifier closure remains isolated from persistence. Change it only when changing its grammar, preserving unknown-placeholder, case, Unicode slicing, and modifier-order behavior.

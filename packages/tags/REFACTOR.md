# Refactor plan

Reviewed against the working tree on 2026-09-05. Preserve additive assignment, explicit drop behavior, public model entry points, and morph/tenant isolation.

## 1. Priority: high — make bulk operations use the correct database boundary

`Tag` honors `phpinnacle-tags.connection`, but `manage()`, `clear()`, `retrieve()`, and `doSyncQuery()` use the default `DB` connection for transactions or raw queries. Configuring a separate tag connection can split tag creation and pivot writes across databases.

- Reproduce operations with the tag connection different from the default. Run transactions and tag/pivot queries on the same intended connection.
- `doSyncQuery()` selects IDs from the source model table inside its insert. Specify behavior when that table is on a different connection; the existing raw SQL cannot assume it is available on the tag connection. Prefer the already selected model IDs where that preserves the required existence semantics.
- Cover assignment, repeat assignment, drop, empty input, multiple morph types with equal IDs, and rollback. Verify the query only affects selected records and authorized action execution.

Acceptance: no partial tag/pivot write or cross-record deletion occurs, including PostgreSQL execution of the bulk SQL. Existing tests only cover metadata and plugin identity.

## 2. Priority: medium — isolate bulk SQL only where it clarifies ownership

An internal database adapter may own chunked pivot writes and transaction orchestration. Keep `Tag::manage()` compatible and tag definition/lifecycle on the model. Do not move every model method into a service just because it is static.

Share the actual taggable join/type/ID predicate between `clear()` and `retrieve()` while retaining their respective delete/read behavior. Bind data values through the database layer; no validation or repair of trusted model IDs is needed.

## 3. Priority: low, measurement required — batch tag definition

Measure query count for realistic bulk selections before replacing `define()`'s `firstOrCreate()` calls. An insert-ignore path bypasses Eloquent creation events, UUID/default/color behavior, and possibly tenant assignment. It is not automatically behavior-preserving.

Proceed only with explicit lifecycle equivalence and tests for existing colors, new IDs/timestamps, normalized names, tenant-specific uniqueness, and concurrent definitions. Preserve the current empty-tag and `drop` semantics unless a separate bug fix changes them. Do not add a batching abstraction merely to reduce a few queries.

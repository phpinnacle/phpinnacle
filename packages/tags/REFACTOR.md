# Refactor plan

Reviewed against the working tree on 2026-09-05. Preserve additive assignment, explicit drop behavior, public model entry points, and morph/tenant isolation.

## 1. Completed — make bulk operations use the correct database boundary

Tag definitions, pivot reads/writes, and bulk transactions now use the configured tag connection. Source IDs are checked on each model's own connection, preserving missing-record exclusion without cross-database SQL. Pivot assignments use bound query-builder inserts and retain idempotent duplicate handling.

`TagConnectionTest` covers separate connections, repeated assignment, drop/clear isolation, equal IDs across morph types, empty selections, missing source records, and rollback of definitions plus pivot writes. Run it with `TAGS_PGSQL_URL` to verify PostgreSQL execution; its default uses independent SQLite databases. Bulk-action tests now execute assignment SQL instead of asserting a mocked SQL string.

## 2. Priority: medium — isolate bulk SQL only where it clarifies ownership

An internal database adapter may own chunked pivot writes and transaction orchestration. Keep `Tag::manage()` compatible and tag definition/lifecycle on the model. Do not move every model method into a service just because it is static.

Share the actual taggable join/type/ID predicate between `clear()` and `retrieve()` while retaining their respective delete/read behavior. Bind data values through the database layer; no validation or repair of trusted model IDs is needed.

## 3. Priority: low, measurement required — batch tag definition

Measure query count for realistic bulk selections before replacing `define()`'s `firstOrCreate()` calls. An insert-ignore path bypasses Eloquent creation events, UUID/default/color behavior, and possibly tenant assignment. It is not automatically behavior-preserving.

Proceed only with explicit lifecycle equivalence and tests for existing colors, new IDs/timestamps, normalized names, tenant-specific uniqueness, and concurrent definitions. Preserve the current empty-tag and `drop` semantics unless a separate bug fix changes them. Do not add a batching abstraction merely to reduce a few queries.

# Refactor plan

Reviewed against the working tree on 2026-09-05. Establish relation correctness before sharing their implementation or optimizing matching.

## 1. Completed — reconcile lazy, eager, and existence queries

Lazy and eager predicates now use the same ancestor/descendant direction as existence queries. Eager matching selects the correct relatives, and non-root nodes can load their ancestors. PHP path matching respects complete labels while preserving the helper's inclusive equality; model relations still exclude self.

`TreeRelationsTest` verifies all three query modes on PostgreSQL/ltree with roots, children, grandchildren, siblings with overlapping label prefixes, a separate root, and custom path/key columns. Set `DENDROS_PGSQL_URL` to a dedicated test database to run it.

## 2. Priority: low — share only proven relation duplication

After the direction tests pass, share common relation initialization and predicate construction only if doing so reduces maintenance of both classes. Keep model behavior on `TreeNode` implementations and SQL behavior at the database/relation boundary. Use precise model types rather than adding more runtime model validation.

## Deferred pending measurement

Both `match()` methods scan results for each parent. Benchmark a representative wide/deep tree before introducing prefix indexes. A dictionary keyed by exact path alone does not solve ancestor/descendant lookup. Any optimization must preserve collection keys, ordering, self-exclusion, and relation membership without changing SQL semantics.

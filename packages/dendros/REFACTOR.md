# Refactor plan

Reviewed against the working tree on 2026-09-05. Establish relation correctness before sharing their implementation or optimizing matching.

## 1. Priority: high — reconcile lazy, eager, and existence queries

`Ancestors` uses `?::ltree @> column` and matches results with the parent's `isAncestorOf()`; `Descendants` uses the opposite direction. Their existence queries put the related column on the left instead. `Ancestors::getResults()` also returns an empty collection for non-root nodes. The current `TreeTest` only tests in-memory comparisons, so these paths are not checked against each other.

- Add PostgreSQL integration cases for root, child, grandchild, and sibling nodes. Compare lazy loading, eager loading, and `whereHas()` results, including self-exclusion and empty results.
- Correct confirmed direction/root errors in a separate behavior-fix step. Preserve custom path/key columns and query bindings.
- Cover label boundaries in `Database/Path::isAncestorOf()`: `root.a` must not match `root.ab`. Its current string-prefix comparison does not distinguish them. Decide and test the direct helper's equality semantics separately from model-level self-exclusion.

Acceptance: all three query modes return the same ancestor/descendant sets and PHP path matching agrees with `ltree` label boundaries.

## 2. Priority: low — share only proven relation duplication

After the direction tests pass, share common relation initialization and predicate construction only if doing so reduces maintenance of both classes. Keep model behavior on `TreeNode` implementations and SQL behavior at the database/relation boundary. Use precise model types rather than adding more runtime model validation.

## Deferred pending measurement

Both `match()` methods scan results for each parent. Benchmark a representative wide/deep tree before introducing prefix indexes. A dictionary keyed by exact path alone does not solve ancestor/descendant lookup. Any optimization must preserve collection keys, ordering, self-exclusion, and relation membership without changing SQL semantics.

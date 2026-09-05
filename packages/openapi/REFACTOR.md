# Refactor plan

Reviewed against the working tree on 2026-09-05. Keep `RuleFactory::make()` and its documented subset of schema translation; do not expand this into a general OpenAPI validator.

## 1. Priority: high — preserve caller-supplied rules during property traversal

The README promises that the second argument adds application rules, but `walkProperties()` assigns `$rules[$key] = $this->fieldRules($property)`, replacing extras on the same key. `allOf` also passes the entire accumulator recursively and merges it back. Current tests omit caller-rule collisions and `allOf` composition.

- First reproduce the README's email example with a caller-supplied rule and a nested prefixed equivalent.
- Generate schema rules independently, then combine caller rules and composition branches deliberately. Keep every application rule and the established rule order/deduplication semantics, including rule objects and closures.
- Test nested properties, overlapping/disjoint `allOf` branches, and interaction with prefixed `oneOf`.

Acceptance: schema translation cannot discard application validation, and rules from an unrelated branch are not repeatedly reprocessed. This is a correctness fix as well as an accumulator simplification.

## 2. Priority: low — extract oneOf only if its behavior grows

The existing closure is small and already exercised through Laravel's validator. A separate rule class is optional; if introduced, keep the exact required-set matching semantics, translated failures, optional parent object, and prefixed-object limitation. It must not become full branch-schema validation under the same name without an explicit behavior change.

## Verification and exclusions

Extend `tests/Unit/RuleFactoryTest.php` with missing behavior rather than converting its existing short tests to datasets for their own sake. Use datasets where new equivalent scalar constraints warrant them. Treat schema objects as trusted developer definitions; do not add a defensive schema parser or malformed-definition test matrix.

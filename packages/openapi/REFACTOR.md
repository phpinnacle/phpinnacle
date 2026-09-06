# Refactor plan

Reviewed against the working tree on 2026-09-05. Keep `RuleFactory::make()` and its documented subset of schema translation; do not expand this into a general OpenAPI validator.

## 1. Completed — preserve caller-supplied rules during property traversal

Property rules and composition branches are generated independently, then merged with application rules. Existing ordering and deduplication are preserved. Regression tests cover root and prefixed extras, rule objects and closures, nested properties, overlapping/disjoint `allOf` branches, and prefixed `oneOf` validation.

## 2. Priority: low — extract oneOf only if its behavior grows

The existing closure is small and already exercised through Laravel's validator. A separate rule class is optional; if introduced, keep the exact required-set matching semantics, translated failures, optional parent object, and prefixed-object limitation. It must not become full branch-schema validation under the same name without an explicit behavior change.

## Verification and exclusions

Extend `tests/Unit/RuleFactoryTest.php` with missing behavior rather than converting its existing short tests to datasets for their own sake. Use datasets where new equivalent scalar constraints warrant them. Treat schema objects as trusted developer definitions; do not add a defensive schema parser or malformed-definition test matrix.

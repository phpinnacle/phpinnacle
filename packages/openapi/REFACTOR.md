# Refactor

Only local, behavior-preserving cleanup is listed here. Public API changes and package-wide redesigns are intentionally excluded.

## 1. Use a single rule accumulator

Stop passing the full rule map into every recursive `make()` call; let property traversal return only newly generated rules and merge them once at each composition boundary.

## 2. Extract the `oneOf` validation rule

Replace the captured closure returned by `oneOfRule()` with a package-local validation rule class so required-set matching can be tested directly and `RuleFactory` remains a schema translator.

## 3. Convert repeated constraint tests to datasets

Use datasets for scalar types, formats, minimum/maximum constraints, nullable fields, and enums, keeping separate focused tests for nesting, `allOf`, and `oneOf`.

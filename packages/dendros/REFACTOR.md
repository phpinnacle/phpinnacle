# Refactor

Only local, behavior-preserving cleanup is listed here. Public API changes and package-wide redesigns are intentionally excluded.

## 1. Share symmetric tree relation plumbing

Extract the identical initialization, model validation, and result-matching scaffolding from `Ancestors` and `Descendants`, leaving only their opposite `ltree` predicates in each relation.

## 2. Centralize `ltree` expressions

Move raw `@>` and `<@` expression construction into the existing `Path` support class so relation classes use one quoting and binding path.

## 3. Index eager-load matching

Build a path-based lookup once when matching eager-loaded tree relations instead of filtering the full result collection separately for every parent model.

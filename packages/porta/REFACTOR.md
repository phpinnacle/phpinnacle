# Refactor

Only local, behavior-preserving cleanup is listed here. Public API changes and package-wide redesigns are intentionally excluded.

## 1. Remove inline Blade from `TransformType`

Move transform preview markup into package views or Filament components so the enum returns structured preview data instead of owning UI templates.

## 2. Share JSON path mutation plumbing

Extract the repeated `JsonObject` creation, path lookup, non-array guard, and final value conversion used by the transform methods into one internal mutation helper.

## 3. Centralize webhook status transitions

Use one private transition method for progress, completion, failure, and retry timestamps, and make the failure path accept a non-null `Throwable` instead of declaring a nullable value that is immediately dereferenced.

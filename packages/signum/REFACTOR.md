# Refactor

Only local, behavior-preserving cleanup is listed here. Public API changes and package-wide redesigns are intentionally excluded.

## 1. Isolate target-aware creation

Keep the public `Alert::create(Model, array)` entry point, but delegate target assignment and persistence to a named internal method so the package-specific construction path is isolated from the rest of the model.

## 2. Pass the actor explicitly

Set `created_by` at the action or service boundary instead of reading `Auth` from a model event, making alert creation deterministic in queues, commands, and tests.

## 3. Share target filtering

Introduce a `forTarget()` query scope and use it in `list()` and future target-specific operations so morph type and ID constraints remain in one place.

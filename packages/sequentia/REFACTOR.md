# Refactor

Only local, behavior-preserving cleanup is listed here. Public API changes and package-wide redesigns are intentionally excluded.

## 1. Separate counter storage from model watching

Move `load()`, counter hashing, and the PostgreSQL upsert from `SequenceWatcher` into a counter store, leaving the watcher responsible for registration and Eloquent events.

## 2. Replace static registration state

Keep registered schemes and watched model classes in a container-managed service rather than static arrays, making package state resettable in tests and safe across long-running workers.

## 3. Isolate pattern modifiers

Move substring modifier parsing from the `preg_replace_callback()` closure into a named private method so placeholder lookup, case conversion, and slicing can be tested independently.

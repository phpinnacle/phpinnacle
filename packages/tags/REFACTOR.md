# Refactor

Only local, behavior-preserving cleanup is listed here. Public API changes and package-wide redesigns are intentionally excluded.

## 1. Move bulk synchronization out of the model

Extract `manage()`, `manageSync()`, `manageDrop()`, and the raw insert query into a dedicated bulk tag operation, leaving thin public model entry points for compatibility.

## 2. Share taggable constraints

Build the taggable join and morph constraints once and reuse them in `clear()` and `retrieve()` instead of maintaining parallel query fragments.

## 3. Define tags in batches

Replace the `firstOrCreate()` call per normalized tag with one insert-ignore and one ID lookup per morph type, preserving names and generated colors while reducing query count.

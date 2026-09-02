# Refactor

Only local, behavior-preserving cleanup is listed here. Public API changes and package-wide redesigns are intentionally excluded.

## 1. Share holder constraints

Introduce a `forHolder()` query scope and use it in `clear()` and `fetch()` so holder type, holder ID, and folder constraints are assembled in one place.

## 2. Reuse image upload configuration

Build the common disk, MIME type, editor, and resize settings once and apply them to both `MediaUpload` and the upload nested in `MediaGallery`.

## 3. Isolate file transfer

Move `performUpload()`, `performCopy()`, and `performMove()` from the Filament component into a package-local storage operation used by both upload fields, preserving the current callbacks and return values.

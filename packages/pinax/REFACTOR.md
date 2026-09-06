# Refactor plan

Reviewed against the working tree on 2026-09-05. Preserve holder/folder isolation, upload return values, model lifecycle, and public component configuration.

## 1. Completed — establish upload and persistence behavior

Copy and move now stream from the temporary file's own storage to the configured destination and check the write result. Moves remove the source only after a successful write. Both fields read MIME type and size from the destination, preserving metadata after source removal. Missing files and failed transfers return no stored value; no successful media metadata is created.

`MediaUploadTest` covers both upload callbacks, copy/move, same/different disks, custom directories, public/private visibility, failed writes, missing files, and source retention/removal. The media-ID and gallery-path return contracts remain intact.

## 2. Priority: medium — keep holder constraints in Media

Factor the repeated holder morph type, holder ID, and folder predicate from `clear()` and `fetch()` into a model scope if both paths remain clearer. Keep listing order and excluded IDs intact.

Acceptance: clearing one folder does not remove another holder's or folder's records, including two model types with equal IDs. Test the configured database connection. Do not add a repository service around these model-owned queries.

## 3. Priority: medium, conditional — isolate actual storage operations

Both fields already reuse `MediaUpload::performUpload()`. Extraction is useful only if it lets a storage adapter work with explicit disk/path/visibility inputs instead of receiving a Filament component. Keep a compatible public `performUpload()` entry point and leave Livewire callbacks/component evaluation in the fields.

## Deferred or corrected

- Shared upload configuration must not apply gallery-only 4096px resizing to `MediaUpload`, which currently has no such default. The remaining image/editor/MIME setup is small enough to keep explicit.
- The review's `composer lint` reports a redundant `marks` fallback in `Mark.php`. Remove it when touching that typed-state path; do not add downstream validation or weaken its array shape.

# Refactor plan

Reviewed against the working tree on 2026-09-05. Preserve holder/folder isolation, upload return values, model lifecycle, and public component configuration.

## 1. Priority: high — establish upload and persistence behavior

The only current package test exercises `Mark`; neither upload flow nor database operations are covered. `MediaUpload::performMove()` operates on the destination disk using the temporary file's path, and callers persist metadata after transfer.

Before moving this code, use fake storage and database fixtures to cover copy/move, custom directories, missing temporary files, public/private visibility, and the different return values: a media ID for `MediaUpload`, a path for `MediaGallery`. Include temporary and destination disks that differ; reproduce any failed-move or orphaned-metadata behavior separately.

Acceptance: successful uploads store matching file metadata; failed transfers do not create successful media records. Preserve existing callback order and missing-file semantics during extraction. New compensation/deletion behavior is a separate fix, not an incidental refactor.

## 2. Priority: medium — keep holder constraints in Media

Factor the repeated holder morph type, holder ID, and folder predicate from `clear()` and `fetch()` into a model scope if both paths remain clearer. Keep listing order and excluded IDs intact.

Acceptance: clearing one folder does not remove another holder's or folder's records, including two model types with equal IDs. Test the configured database connection. Do not add a repository service around these model-owned queries.

## 3. Priority: medium, conditional — isolate actual storage operations

Both fields already reuse `MediaUpload::performUpload()`. Extraction is useful only if it lets a storage adapter work with explicit disk/path/visibility inputs instead of receiving a Filament component. Keep a compatible public `performUpload()` entry point and leave Livewire callbacks/component evaluation in the fields.

## Deferred or corrected

- Shared upload configuration must not apply gallery-only 4096px resizing to `MediaUpload`, which currently has no such default. The remaining image/editor/MIME setup is small enough to keep explicit.
- The review's `composer lint` reports a redundant `marks` fallback in `Mark.php`. Remove it when touching that typed-state path; do not add downstream validation or weaken its array shape.

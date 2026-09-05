# Refactor plan

Reviewed against the working tree on 2026-09-05. The package currently has no PHP tests under `tests`; executable behavior coverage is a prerequisite to lifecycle refactoring.

## 1. Priority: high — keep selected section, options, and default consistent

`CreateDocument::section()` changes the section and calls `getTemplates()`, but that method returns the existing cache whenever `templates` is non-null. Reusing an action for another section can therefore retain the previous options/default.

Reproduce section A followed by section B on the same action. Either invalidate derived state when the section changes or resolve options/default together for the current action evaluation. Do not replace a small cache with several services or uncontrolled repeated queries.

Acceptance: options, default template, and generated number refer to the same current section; cover no templates, no explicit default, inactive/history templates, and reopening the action after a template changes. Mark observable cache corrections as behavior fixes.

## 2. Priority: medium — make template lifecycle behavior explicit on the model

`Template::booted()` enforces defaults and creates a previous-version snapshot when content changes. Establish database tests for content edits, metadata-only edits, default switching, and rendered document snapshot stability before extracting named private model methods.

Check that default enforcement respects the intended tenant/section boundary and that failure during a save does not leave a misleading history/default state. If those tests expose a missing constraint or transaction, address it separately from moving closures. Keep persisted-state behavior on `Template`; an observer is not required merely to reduce its size.

Acceptance: the same Eloquent events produce the same versions, links to history, and existing document content for successful operations.

## Deferred

Shared number/date schema construction in `CreateDocument` and `ManageDocuments` is lower value than cache/lifecycle coverage. Extract only genuinely identical fields when modifying both forms, preserving validation, defaults, callback context, and section-specific fields. Do not change the public Section callbacks, rendering engines, or Sequentia API as part of this cleanup.

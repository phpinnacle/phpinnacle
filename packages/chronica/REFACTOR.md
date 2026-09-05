# Refactor plan

Reviewed against the working tree on 2026-09-05. Preserve the timeline payload, formatting callbacks, configured activity model, and authorization of reversals.

## 1. Priority: high — make historical casting independent of the live subject

`Timeline::castValue()` calls `setRawAttributes()` on the loaded activity subject. Presentation then uses that same subject for relation titles and `Gate::allows()`. When the subject is deleted, the fallback instantiates `subject_type` directly, which may be a morph alias.

- Reproduce rendering with a relation title and an authorization policy that read attributes other than the value being formatted. Verify that rendering does not replace the loaded subject's attributes.
- Add deleted-subject coverage with a registered morph map. Resolve the model class through Eloquent's morph contract and cast historical values on a detached model.
- Treat changed titles, authorization outcomes, and morph-alias handling as correctness fixes before any presenter extraction. Do not add repair logic for malformed package-owned history.

Acceptance: historical values retain their casts, the live subject remains intact, and both allowed and denied reversals still use the correct subject. Extend `tests/Unit/TimelineTest.php`, including an unrelated activity ID and a deleted subject.

## 2. Priority: medium — isolate cohesive value presentation

After the casting fix, consider extracting the casting and value-payload pipeline (`castValue()`, `presentableValue()`, `formatValue()`) into an internal presenter. Pass only the formatting settings it needs; keep timeline composition and revert orchestration in `Timeline`.

Acceptance: existing scalar/JSON output, custom callbacks, enum metadata, null/boolean icons, excluded attributes, and empty-value behavior remain unchanged. Avoid a second mutable copy of all timeline configuration.

## Updated assessment

- Query assembly is already in `activityQuery()`, shared by listing and `revert()`. A new query object would currently add indirection without eliminating duplication; keep this method and its subject constraints together.
- Repeated relation morph lookup in `modelLabel()` is a possible local optimization, not a priority. Resolve metadata per render only if measurement justifies it; never cache subjects or historical cast state across renders.

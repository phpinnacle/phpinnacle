# Refactor plan

Reviewed against the working tree on 2026-09-05. Preserve the timeline payload, formatting callbacks, configured activity model, and authorization of reversals.

## 1. Completed — make historical casting independent of the live subject

Historical casting now uses a copy of the live subject or a detached model resolved through Eloquent's morph map. Timeline tests cover unchanged relation titles and authorization state, allowed/denied reversals, unrelated history, and deleted subjects stored as class names or morph aliases. Deleted-subject coverage includes enum presentation and typed formatting callbacks.

## 2. Completed — isolate cohesive value presentation

`Timeline\ValueFormatter` owns historical casts and value presentation. Timeline composition and revert orchestration remain in `Timeline`; existing formatting and visibility tests preserve the payload contract.

## Updated assessment

- Query assembly is already in `activityQuery()`, shared by listing and `revert()`. A new query object would currently add indirection without eliminating duplication; keep this method and its subject constraints together.
- Repeated relation morph lookup in `modelLabel()` is a possible local optimization, not a priority. Resolve metadata per render only if measurement justifies it; never cache subjects or historical cast state across renders.

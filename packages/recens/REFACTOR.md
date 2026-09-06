# Refactor status

Completed on 2026-09-06 for 1.2.0 after implementing the package ideas.

## Recent-entry ownership

- Reproduced the URL-only identity collision with two users visiting the same canonical URL.
- IDs now include user, panel, configured tenant and query-free URL. Reads and mutations use matching ownership and context predicates; repeated visits update only their own entry.
- The migration rekeys legacy entries using their stored owner and tenant while leaving the unknown panel unassigned. Records are preserved until ordinary pruning; no runtime fallback claims old rows.
- Tests cover recording, canonicalization, repeated visits, pin ordering and limits, pruning, migration and rollback, configured connections, panel guards, and menu authorization across users, panels and tenants.

## Recorder lifetime

- Reproduced reuse of the first panel's callback after switching panels and resetting request scope in one application.
- Each plugin instance now owns its recorder. Container resolution selects the current panel's recorder, retaining trusted registrations across request resets without mixing panels.
- Public `Recent::record(array)`, `Recorder::register()` and plugin declarations remain available; persistence stays on `Recent`.

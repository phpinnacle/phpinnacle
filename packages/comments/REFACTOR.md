# Refactor

Only local, behavior-preserving cleanup is listed here. Public API changes and package-wide redesigns are intentionally excluded.

## 1. Share the subject query

Introduce a `forSubject()` query scope and use it from `count()` and `list()` so morph type and ID filtering cannot drift between the two paths.

## 2. Eager-load comment authors

Load `author` in `Comment::list()` because every rendered comment reads that relationship, removing the current per-comment lazy query.

## 3. Consolidate create authorization

Use one private authorization check in the Livewire component for both `form()` and `create()`, including the unauthenticated case, instead of resolving and checking the current user twice.

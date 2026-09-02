# Refactor

Only local, behavior-preserving cleanup is listed here. Public API changes and package-wide redesigns are intentionally excluded.

## 1. Separate icon discovery from label rendering

Cache plain icon names after filesystem discovery and render labels only for the requested page, keeping `getIcons()` from eagerly rendering HTML for every icon.

## 2. Scope the icon cache key

Derive the cache key from the selected sets and excluded prefixes and suffixes instead of using the global `icons-select` key for every picker configuration.

## 3. Share dimension validation

Use one private positive-integer evaluator for rows and columns, removing the duplicated closure evaluation and exception branches.

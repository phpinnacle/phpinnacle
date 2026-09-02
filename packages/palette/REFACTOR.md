# Refactor

Only local, behavior-preserving cleanup is listed here. Public API changes and package-wide redesigns are intentionally excluded.

## 1. Remove the static color registry cache

Read colors from `FilamentColor` when resolving a semantic color instead of retaining them in `Color::$colors`, avoiding stale cross-request state under Octane.

## 2. Keep palette conversion in `Color`

Move the repeated palette-to-hex mapping used by `ColorPicker::getTailwindColors()` and `getThemeColors()` into `Color`, leaving the field responsible only for configured sources and state.

## 3. Normalize source mutations once

Route `enable()`, `disable()`, `format()`, and `getSources()` through one private normalization method that deduplicates sources and applies semantic-format compatibility consistently.

# Refactor plan

Reviewed against the working tree on 2026-09-05. The stale semantic-color cache is the clear priority; most proposed field abstractions are unnecessary.

## 1. Priority: high — remove the static semantic-color snapshot

`Color::resolve()` populates `Color::$colors` on first use and never refreshes it. The picker itself reads `FilamentColor::getColors()` directly, so a later theme registration can give the picker and `resolve()` different colors in the same process.

Read the current Filament registry when resolving an alias. Keep palette shade aliases, hex conversion, and the existing fallback to the current primary color.

Acceptance: in `tests/Unit/ColorTest.php`, resolve an alias, replace the registered palette, then resolve again and observe the new color without resetting package internals. Verify a missing name falls back to the new primary. This corrects stale behavior and does not require a new cache or service binding.

## Removed from the active queue

- Palette conversion is already centralized in `Color::hex()`. `getTailwindColors()` maps selected shades, while `getThemeColors()` maps one default shade per semantic color. Their different output shapes do not justify another conversion layer.
- A universal source normalizer could change fluent semantics. `enable()` deduplicates, `disable()` removes, `format(Semantic)` replaces sources, and `getSources()` filters compatibility without mutating configuration. Preserve this division; do not silently drop configured sources during reads or format switches.

## Conditional follow-up

If source handling changes for a concrete requirement, extend `tests/Unit/ColorPickerTest.php` with order-sensitive `enable()`/`disable()`/`format()` sequences and a switch back from Semantic. Keep alpha preservation, source order, shade selection, and stored values unchanged. Run asset/browser checks only if the picker UI or JavaScript changes.

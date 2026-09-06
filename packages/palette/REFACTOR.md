# Refactor plan

Reviewed against the working tree on 2026-09-05. The active refactor was completed on 2026-09-06; no active items remain.

## Completed: remove the static semantic-color snapshot

`Color::resolve()` now reads `FilamentColor::getColors()` on every call, matching the picker when the active Filament registry changes in the same process. The static semantic-color snapshot has been removed.

Palette shade aliases, hex conversion, and the fallback to the current primary color are preserved.

`tests/Unit/ColorTest.php` resolves an alias, replaces the Filament registry through its public facade, then verifies the new alias color and primary fallback without resetting package internals. Filament caches colors within each registry instance, so registering more colors after its first read does not replace that instance's cached palette.

## Removed from the active queue

- Palette conversion is already centralized in `Color::hex()`. `getTailwindColors()` maps selected shades, while `getThemeColors()` maps one default shade per semantic color. Their different output shapes do not justify another conversion layer.
- A universal source normalizer could change fluent semantics. `enable()` deduplicates, `disable()` removes, `format(Semantic)` replaces sources, and `getSources()` filters compatibility without mutating configuration. Preserve this division; do not silently drop configured sources during reads or format switches.

## Conditional follow-up

If source handling changes for a concrete requirement, extend `tests/Unit/ColorPickerTest.php` with order-sensitive `enable()`/`disable()`/`format()` sequences and a switch back from Semantic. Keep alpha preservation, source order, shade selection, and stored values unchanged. Run asset/browser checks only if the picker UI or JavaScript changes.

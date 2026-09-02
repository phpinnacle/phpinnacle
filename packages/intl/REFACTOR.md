# Refactor

Only local, behavior-preserving cleanup is listed here. Public API changes and package-wide redesigns are intentionally excluded.

## 1. Share picker defaults

Move the repeated label, placeholder, icon, search, debounce, and option-limit setup from the four picker classes into a small package-local base picker.

## 2. Defer dictionary loading

Pass dictionary lookups to Filament as option closures instead of resolving hundreds of localized options during every component setup.

## 3. Remove duplicate language normalization

Pass the validated string directly from `LanguageCode` to `Languages::exists()`, which already trims and lowercases the code, instead of normalizing it twice.

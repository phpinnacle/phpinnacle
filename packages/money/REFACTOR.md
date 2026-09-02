# Refactor

Only local, behavior-preserving cleanup is listed here. Public API changes and package-wide redesigns are intentionally excluded.

## 1. Route comparisons through `compare()`

Implement `eq()`, `gt()`, and `lt()` through the existing `compare()` method so currency validation and amount comparison have one path.

## 2. Split decimal parsing into named stages

Separate sign normalization, fractional-digit adjustment, and rounding inside `MoneyParser` into small private methods while retaining the existing integer-only algorithm.

## 3. Rename internal input bounds

Rename `MoneyInput`'s private `minValue` and `maxValue` state to lower and upper bounds and use one private rule-registration method, removing the current inversion between `greater()`, `lesser()`, and their stored fields.

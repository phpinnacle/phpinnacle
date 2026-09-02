# Refactor

Only local, behavior-preserving cleanup is listed here. Public API changes and package-wide redesigns are intentionally excluded.

## 1. Stop traversing the rendered filter form

Have `CombinedFilter` obtain indicator labels through its configured `Filter` objects rather than walking Filament's internal component hierarchy to recover select options.

## 2. Normalize selected-value checks

Use one value-presence rule in `CombinedFilter\Filter` for query application and indicator generation; the current `blank()` and `empty()` checks treat values such as `0` differently.

## 3. Namespace model mutation helpers

Move the implementations of `reset_sort()` and `reset_default()` into a namespaced support class and leave the existing global functions as thin compatibility entry points.

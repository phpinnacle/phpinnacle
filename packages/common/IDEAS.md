# Ideas

Only small, additive features are listed here. Refactors and package-wide redesigns are intentionally excluded.

## 1. Open-ended range input

Offer optional manual `from` and `to` inputs for `RangeFilter`, allowing either boundary to remain empty when a slider is unsuitable for a wide or unbounded range.

## 2. Multi-value combined filters

Allow entries in `CombinedFilter` to select multiple values and choose whether the resulting relationship constraints use all-value or any-value semantics.

## 3. Relationship count filter

Extend `HasFilter` with minimum and maximum related-record counts in addition to the current has-or-does-not-have choice.

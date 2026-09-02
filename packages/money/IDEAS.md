# Ideas

Only small, additive features are listed here. Refactors and package-wide redesigns are intentionally excluded.

## 1. Locale-aware parsing

Parse decimal and grouping separators for an explicit locale so values such as `1 234,50` are handled without ambiguous string replacement.

## 2. Division with explicit rounding

Add division and ratio operations that require a rounding mode and keep all results in integer minor units.

## 3. Caller-supplied currency conversion

Convert to a target currency from an explicit exchange rate, scale, and rounding mode while deliberately leaving rate retrieval to the application.

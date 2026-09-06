# Refactor plan

Reviewed against the working tree on 2026-09-05. Preserve integer minor units, public comparison APIs, and the existing cross-currency zero exception.

## 1. Completed — resolve MoneyInput/MoneyRule contract mismatches

`greater()` now installs minimum comparisons and `lesser()` maximum comparisons. Rule factories preserve integer and Money bounds; strings remain field references. Conditional `required()` enforces positivity without replacing explicit bounds. Malformed input is rejected with translated Laravel validation errors, while Money objects retain their domain contracts. The README documents bound and nullable semantics; form-submission regression tests cover literal, referenced, and closure bounds, equality, and conditional required state.

## 2. Priority: medium — characterize decimal rounding before reorganizing it

`MoneyParser::parseDecimal()` interleaves sign handling, digit adjustment, carry propagation, and conversion to an integer. Existing tests do not cover this algorithm comprehensively.

Add focused cases for currencies with zero/two/three fraction digits, negative amounts, carry across the decimal boundary, leading zeros, and accepted separators. Only then extract cohesive private stages if they clarify the algorithm. Any rounding or integer-overflow defect needs a separate failing example and an explicit behavior decision; do not switch to floating-point arithmetic.

## 3. Priority: low — reuse compare()

Route `eq()`, `gt()`, and `lt()` through `compare()` when editing this area. Keep the inclusive flag and the asymmetric zero-currency guard exactly as they are. Existing `tests/MoneyTest.php` comparison cases are the starting point; add both operand orders where needed.

Do not introduce a shared validation-rule registration framework solely to remove the short field closures.

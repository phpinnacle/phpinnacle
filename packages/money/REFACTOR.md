# Refactor plan

Reviewed against the working tree on 2026-09-05. Preserve integer minor units, public comparison APIs, and the existing cross-currency zero exception.

## 1. Priority: high — resolve MoneyInput/MoneyRule contract mismatches

`MoneyInput::greater()` installs `lt/lte`, while `lesser()` installs `gt/gte`. The field accepts `Money|string|int` bounds, but `MoneyRule` factories take only a string and interpret it as another field name. This needs an end-to-end validation check, not a rename. The bound properties are protected, and their getters are public, contrary to the old plan's description of private state.

- Reproduce strict/inclusive bounds with literal integers, Money objects, referenced fields, and closures. Include `required()` because it delegates to `greater(0, strict: true)`.
- Specify the intended distinction between a literal bound and a field reference before implementation. Fix confirmed inversions and unsupported bound paths as behavior changes, preserving published signatures and subclass access unless a separate API change is agreed.
- Exercise `MoneyRule::validate()` with malformed user values; validation should report translated failures at the Laravel boundary instead of leaking parser/type errors. Do not repeat those checks on trusted Money objects.

Acceptance: actual form validation accepts values within bounds, rejects values outside them, and handles nullable/required state as documented. Extend `tests/MoneyIntegrationTest.php`; component construction alone is insufficient.

## 2. Priority: medium — characterize decimal rounding before reorganizing it

`MoneyParser::parseDecimal()` interleaves sign handling, digit adjustment, carry propagation, and conversion to an integer. Existing tests do not cover this algorithm comprehensively.

Add focused cases for currencies with zero/two/three fraction digits, negative amounts, carry across the decimal boundary, leading zeros, and accepted separators. Only then extract cohesive private stages if they clarify the algorithm. Any rounding or integer-overflow defect needs a separate failing example and an explicit behavior decision; do not switch to floating-point arithmetic.

## 3. Priority: low — reuse compare()

Route `eq()`, `gt()`, and `lt()` through `compare()` when editing this area. Keep the inclusive flag and the asymmetric zero-currency guard exactly as they are. Existing `tests/MoneyTest.php` comparison cases are the starting point; add both operand orders where needed.

Do not introduce a shared validation-rule registration framework solely to remove the short field closures.

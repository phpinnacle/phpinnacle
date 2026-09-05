# Refactor plan

Reviewed against the working tree on 2026-09-05. Preserve filter callbacks, dependent-select behavior, and the global helper APIs used by other packages.

## 1. Priority: medium — remove indicator dependence on form nesting

`CombinedFilter::setUp()` recovers option labels by traversing the filters form and assuming its first child contains the selects. This couples indicators to a particular Filament component layout.

- Keep access to the actual configured Select and its public option-label evaluation, instead of relying on the rendered hierarchy. Do not evaluate option closures outside their Filament context: they may depend on `Get` and sibling state.
- Keep `Filter` responsible for its indicator. Preserve `indicator()` compatibility if its arguments change internally.
- Add focused integration coverage; current tests only exercise concerns and ranges. Verify static and dependent options, grouped options, zero-valued selections, indicator removal, and dependent-state clearing. Treat newly discovered grouped-label defects separately from the extraction.

Acceptance: the same selection produces the same query and indicator even if the surrounding form layout changes, with no additional option query per indicator when a label is already available.

## 2. Priority: medium — verify configuration and form-cache consistency

`filters()` replaces filters and dependencies but does not invalidate `cachedForm`. Reproduce configuring a new filter set after the schema has been built; if that sequence is supported, invalidate the derived form when its source configuration changes.

Acceptance: the rendered fields, dependency callbacks, query, and indicators all use the current filter set. Keep the cache local to the component.

## Removed from the active queue

- Selected-value normalization is already done: `Filter::apply()` and `indicator()` both use `blank()` in the current working tree. Preserve this in the filter coverage above.
- Moving `reset_sort()` and `reset_default()` to a support class would leave forwarding functions and the same behavior. There is no demonstrated ownership or reuse benefit to that extra layer.

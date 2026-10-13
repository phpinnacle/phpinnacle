# Refactor goals

Preserve filter callbacks, dependent-select behavior, and the public helper APIs
used by other packages. These goals describe future work, not additional features
available today.

## Combined filter indicators

Selected values should keep the same query and readable indicator when the form
layout changes. Indicator removal should clear the intended field. Dependent
options must use current form values.

Future integration coverage should include static and dependent options, grouped
labels, zero-valued selections, indicator removal, and dependent-state clearing.
Any newly discovered behavior defect should be addressed separately from a
refactor.

## Reconfiguring combined filters

Determine whether replacing filter definitions after a form has been displayed
is a supported use case. If supported, the displayed fields, dependencies,
queries and indicators must all reflect the new definitions.

Current tests cover picker behavior, range matching, record creators and page
refreshes. Combined filter integration coverage remains a separate task.

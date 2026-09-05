# Refactor plan

Reviewed against the working tree on 2026-09-05. This package needs no structural redesign; retain the four small public picker classes and dictionary APIs.

## 1. Priority: medium, conditional — defer expensive option loading

Each picker currently calls its dictionary's `list()` during `setUp()`. Measure a form containing hidden or unused pickers and compare setup plus rendering with lazy option closures.

Proceed only if closures avoid meaningful work without repeatedly rebuilding the same dictionary during one render. Preserve option keys, ordering, synthetic defaults, and translated labels. Do not add a static localized-option cache.

Acceptance: unused fields avoid dictionary work, rendered fields retain the same options, and separate requests with different locales do not reuse translated options. Extend `tests/Unit/DictionariesTest.php` with the relevant component lifecycle case; explicitly account for the change from setup-time to evaluation-time locale selection.

## 2. Priority: low — remove redundant rule normalization when touching validation

`LanguageCode::validate()` lowercases a string before `Languages::exists()` lowercases and trims it again. Delegate normalization to the dictionary while retaining the rule's `mixed` type check and translated validation failures.

Acceptance: uppercase and padded language codes keep working; non-string and unknown values still fail at the Laravel validation boundary.

## Removed from the active queue

A shared base picker would hide a short fluent setup and introduce inheritance for one use per class. Defaults are not identical: the country picker allows HTML, while the timezone picker does not set the same search/debounce options. Keep these differences explicit unless a real shared behavior emerges.

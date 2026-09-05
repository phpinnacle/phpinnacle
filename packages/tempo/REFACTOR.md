# Refactor plan

Reviewed against the working tree on 2026-09-05. No broad picker/calendar redesign is justified. Preserve date-format overrides, event-source contracts, and browser payloads.

## 1. Priority: medium — cover calendar limits before changing the pipeline

`CalendarWidget::fetchCalendarEvents()` already validates the range, selects sources, requests `limit + 1` events per source, sorts, and applies the global limit. Existing tests cover sorting and selected sources; extend them for truncation, invalid ranges, and the difference between null selection (all) and an empty selection (none).

Acceptance: invalid ranges do not call sources, only selected sources run, event ordering remains stable for equal start times, and `truncated` reflects the merged result beyond the global limit. Preserve event source metadata and actionable/URL behavior.

Extract a private selection or payload-mapping method only when it makes a concrete change easier to verify. Keep the orchestration in the widget and event serialization on `CalendarEvent`; do not add a calendar service layer or validate trusted `EventSource` implementations again.

## 2. Priority: low — remove obsolete commented code when touching DatePicker

The commented `stateCast()` block remains in `DatePicker::setUp()` and can be deleted as local cleanup. Do not enable it: that would change hydration/serialization behavior and needs a separate requirement and round-trip tests.

## Deferred

The two mode-to-format matches are short and call different public format getters. Replacing the Moment path with conversion of the PHP mask could bypass subclass overrides such as `getMomentDateFormat()`. A common resolver is worthwhile only if it retains those extension points without becoming more complicated than the matches.

For future picker behavior changes, extend `tests/Unit/FilamentComponentsTest.php` with custom format/timezone and state round trips. Rebuild the existing assets and exercise the browser only when frontend behavior changes; PHP method extraction alone does not justify an asset rewrite.

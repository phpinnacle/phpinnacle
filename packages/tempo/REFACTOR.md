# Refactor

Only local, behavior-preserving cleanup is listed here. Public API changes and package-wide redesigns are intentionally excluded.

## 1. Remove the abandoned state-cast block

Delete the commented-out `stateCast()` implementation from `DatePicker::setUp()`; Git history already preserves it and the live component does not use it.

## 2. Share picker format selection

Use one private mode-to-format resolver for PHP and Moment formats instead of maintaining matching branches in `getMaskFormat()` and `getMomentMaskFormat()`.

## 3. Decompose calendar event fetching in place

Split source selection, event collection, sorting, limiting, and JSON mapping in `CalendarWidget::fetchCalendarEvents()` into named private methods without adding a calendar service layer.

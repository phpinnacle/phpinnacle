# Refactor

Only local, behavior-preserving cleanup is listed here. Public API changes and package-wide redesigns are intentionally excluded.

## 1. Separate activity querying from presentation

Move the activity query currently assembled in `Timeline::items()` behind a focused query object so timeline configuration and rendering no longer own database selection details.

## 2. Isolate activity value formatting

Move attribute casting, scalar/JSON formatting, causer labels, and model labels from `Timeline` into a presenter while keeping the view payload unchanged.

## 3. Reuse morph metadata

Resolve each configured model's morph class once per timeline render instead of repeatedly instantiating model classes in `modelLabel()` and `castValue()`.

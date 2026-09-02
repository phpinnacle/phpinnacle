# Refactor

Only local, behavior-preserving cleanup is listed here. Public API changes and package-wide redesigns are intentionally excluded.

## 1. Finish adopting `RequestValue`

Replace the remaining hand-written positive ID, page, and per-page checks in older request classes with the existing `RequestValue` helpers so validation messages and boundaries have one implementation.

## 2. Standardize paginated response hydration

Use `ResponseValue::objects()` consistently in collection response DTOs instead of keeping a second family of manual `array_map()` and object-validation callbacks.

## 3. Split the client test by API area

Break the large `ClientTest` into files matching the API classes, with transport behavior kept in its own test, without changing the fixture responses or coverage.

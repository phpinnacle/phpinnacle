# Refactor

Only local, behavior-preserving cleanup is listed here. Public API changes and package-wide redesigns are intentionally excluded.

## 1. Use one locale option shape

Have `BabylonPlugin` build one canonical `code => name` map and adapt it only at the locale-switcher view boundary, instead of building row arrays and re-indexing them with `array_column()` in `profile()`.

## 2. Centralize preference keys

Define the Babylon preference group and locale key once and reuse them in the profile schema and `SetLocale` middleware, removing the repeated string literals.

## 3. Separate locale resolution from application

Move the session, preference, and application fallback chain into a side-effect-free method; keep `handle()` responsible only for applying the resolved locale and continuing the request.

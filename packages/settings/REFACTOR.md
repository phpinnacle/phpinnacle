# Refactor

Only local, behavior-preserving cleanup is listed here. Public API changes and package-wide redesigns are intentionally excluded.

## 1. Load only the requested settings group

Replace `SettingsStorage::init()` loading every tenant and section with a query for the current tenant and requested section, then cache only that tenant/section key.

## 2. Batch settings upserts

Build all setting rows first, calculate the group UUID once, and issue one `upsert()` instead of running one database statement for every field.

## 3. Isolate schema coercion

Move reflection metadata and value coercion into named private methods, correct the private `coerse()` name, and cover backed enums, nullable values, and built-in types with focused tests.

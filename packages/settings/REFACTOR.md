# Refactor plan

Reviewed against the working tree on 2026-09-05. Fix storage isolation and consistency before optimizing queries. Existing tests cover `DefinitionRegistry`, not `SettingsStorage`.

## 1. Priority: high — scope both storage values and hydrated objects

`SettingsStorage` is a singleton caching values by tenant/section, and `SettingsServiceProvider` separately binds configured settings objects as singletons. Changing only the storage binding leaves already filled objects available to later requests/tenants.

Separate reusable reflection definitions from request/tenant values. Align storage and hydrated-object lifetimes, retaining registration after scope reset. Cover consecutive requests for tenants A and B and the non-tenant default; if tenant switching within one request is supported, define object resolution for that case explicitly.

Acceptance: resolving a settings object cannot return a previous tenant's values, and fresh requests observe saved values. Preserve public `register()`, `fill()`, `load()`, and `save()` contracts.

## 2. Priority: high — query the intended group on the configured connection

`init()` reads the entire table, while load/save use the default DB connection despite the migration using `phpinnacle-settings.connection`. Missing groups are not cached, causing repeated full-table reads. A broad `catch (Throwable)` makes database failures indistinguishable from absent settings.

Query by tenant and section using the configured connection; cache an empty group explicitly. Let unexpected database failures propagate. If installation before migrations needs special handling, identify that specific lifecycle separately rather than swallowing all failures.

Acceptance: one cold group read touches only its rows, a repeated missing-group read is cached within the allowed lifetime, two configured connections do not mix, and database errors are not reported as successful empty settings.

## 3. Priority: high — make save/load/fill agree

`save()` upserts supplied keys but replaces the cached section with only those keys; a fresh load can therefore return additional persisted keys. `register()` records nullability in the schema, but `coerse()` ignores it while using `settype()`, so nullable scalar `null` may become zero, false, or an empty string.

Define partial-save semantics explicitly (the current database write leaves omitted keys intact), then make immediate and fresh reads agree. Preserve valid nullable values and backed-enum round trips through one storage conversion path. Rename `coerse()` while fixing that path, without building a general coercion framework or revalidating trusted definitions/persisted state.

Acceptance: partial and empty saves, nullable scalars, enums, and a filled typed object have consistent results before and after cache reset. Preserve object defaults for absent keys. Treat observable corrections as fixes.

## 4. Priority: medium — batch writes after consistency is defined

Calculate the existing group UUID once and submit one upsert for the supplied rows. Preserve UUID derivation and JSON representation; update/invalidate the cache only after a successful write. Verify query count and failure atomicity rather than merely moving the loop.

Migration coverage should also address `create_settings_table.php::down()` leaving `preferences` behind. Keep that rollback fix separate from storage refactoring and test it on disposable tables.

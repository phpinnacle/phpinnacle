# Changelog

All notable changes to `recens` will be documented in this file.

## 1.2.1 - 2026-09-06

- Include panel and pin columns in the initial migration and remove registration of the deleted upgrade migration.
- Replace local Eloquent scopes with explicit model query methods.
- Clarify that installations with the pre-1.2 schema require an application migration.

## 1.2.0 - 2026-09-06

- Add stable pinned pages above limited recent history and protect pins from pruning.
- Add authorized menu actions to pin, unpin, remove entries and clear current-panel history while retaining pins.
- Isolate history and deterministic entry IDs by user, panel and configured tenant, including panel auth guards.
- Migrate legacy entries without deleting them or assigning an unknown panel.
- Keep recorder registrations owned by their panel across requests.
- Honor the pruning enabled setting and use the configured user model with the `user_id` column on fresh installations.

## 1.0.0 - 202X-XX-XX

- initial release

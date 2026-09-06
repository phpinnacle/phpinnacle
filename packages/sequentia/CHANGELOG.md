# Changelog

All notable changes to `phpinnacle/sequentia` will be documented in this file.

## 1.1.1 - 2026-09-06

- Make rebuild replace aggregates for represented counter keys atomically, preserving unrelated keys and documenting the required pause in writes.
- Count global counters once per record and tenant across registered bucket schemes.
- Keep registrations and model listeners together across event dispatcher resets.
- Isolate counter storage while preserving hashes, configured connections, public static entry points, and prospective reads.
- Verify rebuild repeatability, rollback, tenant and period boundaries, and concurrent PostgreSQL increments.

## 1.0.0 - 2026-07-17

- Initial release extracted from `phpinnacle/common`.

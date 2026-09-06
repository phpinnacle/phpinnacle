# Ideas status

Implemented in 1.2.0 on 2026-09-06.

## Pinned pages

Users can pin and unpin menu entries. Pins appear in stable pin order above ordinary history, remain outside the configured display limit, and are excluded from pruning.

## History controls

Users can remove one entry or clear ordinary history for the current panel and tenant. Clearing includes entries beyond the visible limit and preserves pinned pages. Removing a pin explicitly deletes it.

## Per-panel history

Entries store their Filament panel identifier and are isolated by authenticated user, panel and configured tenant. Legacy entries retain an unassigned panel until ordinary pruning removes them.

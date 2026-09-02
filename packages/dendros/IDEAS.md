# Ideas

Only small, additive features are listed here. Refactors and package-wide redesigns are intentionally excluded.

## 1. Safe move action

Provide a Filament action for moving a node to another parent, with cycle prevention and a confirmation that shows how many descendants will move with it.

## 2. Breadcrumb component

Add a compact breadcrumb component for tables, forms, and infolists that renders a node's ancestor path and can link to each ancestor.

## 3. Sibling ordering

Optionally persist a configurable sort column and expose drag-and-drop ordering among siblings without changing the `ltree` hierarchy.

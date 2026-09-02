# Ideas

Only small, additive features are listed here. Refactors and package-wide redesigns are intentionally excluded.

## 1. OAuth token helpers

Add framework-agnostic helpers for exchanging authorization codes and refreshing access tokens through the existing PSR HTTP stack.

## 2. File upload workflow

Wrap Canvas's multi-step file upload flow so callers can request an upload target, transfer a file, and receive the resulting file DTO without assembling the protocol manually.

## 3. Modules context

Add typed access to course modules and module items, including listing, creating, updating, and reordering items.

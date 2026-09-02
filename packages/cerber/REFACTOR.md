# Refactor

Only local, behavior-preserving cleanup is listed here. Public API changes and package-wide redesigns are intentionally excluded.

## 1. Decompose plugin registration in place

Split `CerberPlugin::register()` into named private methods for authentication pages, resources, providers, and render hooks without introducing new plugin abstractions.

## 2. Reuse permission checkbox construction

Create one private `CheckboxList` builder inside `PermissionTabs` and use it for custom permissions, resources, pages, and widgets, preserving their labels and option sources.

## 3. Separate the user model's framework concerns

Move the existing MFA storage, Filament identity, tenancy, and permission methods from `User` into narrowly named concerns while keeping every public method on the model through trait composition.

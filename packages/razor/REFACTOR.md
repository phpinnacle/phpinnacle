# Refactor

Only local, behavior-preserving cleanup is listed here. Public API changes and package-wide redesigns are intentionally excluded.

## 1. Name template lifecycle operations

Move default-template enforcement and history snapshot creation from inline model event closures into named private methods or an observer, keeping the same Eloquent events and transaction behavior.

## 2. Reuse document form components

Extract the repeated number and date fields from `CreateDocument` and `ManageDocuments` into one schema builder while leaving action-specific template and parent fields in the action.

## 3. Remove mutable template caching from the action

Derive template options and the default template together for the current section instead of storing them in three mutable `CreateDocument` properties that can become inconsistent across Livewire evaluations.

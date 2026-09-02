# Refactor

Only local, behavior-preserving cleanup is listed here. Public API changes and package-wide redesigns are intentionally excluded.

## 1. Move exception capture out of the model

Extract the body of `Exception::report()` into a reporter service and keep the static method as the public entry point, leaving the Eloquent model focused on persisted state and relationships.

## 2. Isolate request snapshot creation

Build the method, path, query, cookies, headers, body, and IP snapshot in one request-normalization method, including the Livewire original-request handling, so that path can be tested without saving a model.

## 3. Extract sensitive-value presentation

Move `flatten()` and `maskSensitive()` from `ExceptionInfo` into a small presenter used by the schema, keeping the Filament class declarative and making redaction behavior directly testable.

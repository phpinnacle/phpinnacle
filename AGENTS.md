<required-skills>

- You MUST activate the `engineering-discipline` skill before every repository task that may modify files or public behavior, including implementation, bug fixes, refactors, tests, configuration, assets, documentation, cleanup, and code review.
- `engineering-discipline` is additive: activate every other skill required by the task's domain.

</required-skills>

# Repository Guidelines

## Scope and Instruction Precedence

- These instructions apply to the monorepo root and every package below it.
- Each top-level package is independently releasable. A package's own `AGENTS.md` adds to or overrides these rules for files inside that package.
- Before changing a package, read its `AGENTS.md` when present, `README.md`, `composer.json`, tests, and the relevant service provider, plugin, or public entry point.
- Keep behavior in the package that owns it. Cross-package use must go through public APIs and preserve one-way dependency boundaries.

## Language

- Write repository content in English, including documentation, comments, PHPDoc, test descriptions, TODOs, and developer-facing messages.
- Keep intentional product translations and localized UI copy in their target language.

## Scope and Compatibility

- Treat each package as an independently releasable Composer unit. Before changing it, inspect its `README.md`, `composer.json`, service provider, Filament plugin, configuration, migrations, assets, tests, and static-analysis setup as applicable.
- Keep changes inside the package and its declared dependency boundaries. Consume another package through its public API; do not create reverse or cyclic dependencies.
- Preserve observable behavior and public contracts unless the task or package-specific instructions explicitly permit a breaking change.
- Do not add dependencies or new top-level directories without approval.
- Keep Laravel package wiring in the service provider and panel wiring in the Filament plugin. These are composition roots, not homes for runtime domain behavior.

## Design and Ownership

- Implement the smallest complete solution for the current requirement. Do not add speculative features, configuration, compatibility layers, or extension points.
- Prefer existing Laravel, Filament, Livewire, and package components over local framework-like abstractions.
- Use arrays for lists, maps, and serialization boundaries. Convert structured domain state into typed value objects before domain behavior begins.
- Use explicit parameter and return types on named methods, constructor property promotion, descriptive names, and PHPDoc array shapes where arrays are intentional.
- Let Eloquent models own their persisted state, relationships, lifecycle defaults, model-scoped reads, and cohesive state transitions.
- Use services for external APIs, provider protocols, registries, reflection or storage adapters, parsing and formatting, authorization orchestration, and cross-boundary transactions.
- Never store request-, user-, or tenant-specific state in static properties or singleton services under Octane.

## Behavior Follows Owned State

- When a method does not use its declaring object's state and instead operates exclusively on another typed object's state, place that behavior on the typed object when it is universally meaningful to it.
- Keep consumers responsible for orchestration, but do not make them unpack another object's state to implement behavior that belongs to that object.
- Keep pure domain value objects independent of framework infrastructure. Move framework-specific behavior only when the object itself represents an integration or adapter boundary.

## Inputs, Invalid Values, and Errors

- Classify each input boundary before handling it: developer configuration, user or form state, persisted data, and external payloads require different failure semantics.
- Validate user-controlled input through Laravel or Filament validation. Custom rules must type-check `mixed` values, call `$fail(...)->translate()`, and stop invalid state from entering the domain.
- Domain value objects must fail fast on violated invariants. Use `InvalidArgumentException` for invalid caller arguments and named domain exceptions for failures callers need to distinguish.
- Represent expected business outcomes with result objects or enums. Use `null`, `false`, empty collections, and early returns only when absence, availability checks, or idempotent no-ops are part of the method contract.
- Normalize or fall back only when that behavior has explicit domain meaning. Do not silently repair invalid persisted or external data.
- Catch exceptions only where they can be recovered, translated, enriched, cleaned up, or rolled back. Unexpected transactional failures must be rethrown; swallowed `Throwable` is reserved for explicit best-effort work that must not break the primary request.

<trusted-state-and-extension-rules>

## Trusted State and Extension Boundaries

- Treat developer-authored registrations, definitions, presets, callbacks, configuration, and framework objects as trusted code when the extension point declares concrete PHP types or precise PHPDoc.
- Treat current package-owned persisted state as trusted because it was validated when created or changed. Consume it directly without reparsing, revalidation, compatibility fallbacks, or defensive repair.
- Treat values returned by Laravel or Filament validation as trusted after their declared rules pass. Downstream code must use precise types or array shapes and direct access instead of repeating validation.
- Do not add runtime guards, parsers, normalization layers, duplicate checks, or custom exceptions merely to make trusted developer, validated, or package-owned state safer or errors more descriptive.
- Express contracts through parameter and return types, generics, array shapes, PHPDoc, validation rules, static analysis, and runnable documentation. Native PHP, Laravel, or Filament failures are acceptable when a trusted contract is violated.
- Preserve developer-supplied framework semantics. Do not rewrite names, synthesize identifiers, verify callback completeness, or inspect callback result shapes when the declared contract already describes them.
- Validate only genuinely external values and user input that bypass package-owned writers or framework validation. Authorization and query safety remain mandatory at their native Laravel and database boundaries.
- Keep normalization only when it has explicit product meaning, such as canonical serialization, sparse settings, or bounded interactive geometry. Do not use normalization to repair trusted state.
- Throw an exception only when execution cannot continue and neither types nor native PHP, Laravel, Filament, or database behavior can express the failure.
- Test validation at its owning boundary, valid composition, lookups, and external or security boundaries. Do not add malformed trusted-state test matrices or downstream validation tests.

</trusted-state-and-extension-rules>

## Working Method and Verification

- Inspect the relevant implementation and tests before choosing a design. Surface assumptions only when they materially affect public contracts, persisted data, authorization, security, or external side effects.
- Make surgical changes: every changed line must serve the requested outcome, its verification, or cleanup made necessary by the change. Preserve unrelated user work and report unrelated defects instead of fixing them.
- Remove only imports, code, tests, assets, and configuration made obsolete by the current change.
- Define observable success before implementation. Reproduce bugs with the narrowest practical test, cover invalid inputs for validation changes, and establish a passing baseline before refactors.
- Run the narrowest relevant checks first, then broaden according to risk. For changed PHP, run `vendor/bin/pint --dirty --format agent`; run the package's focused Pest tests and PHPStan configuration, plus asset or JavaScript checks when frontend behavior changes.
- Finish by inspecting the diff for scope drift, hidden behavior changes, unnecessary complexity, and incomplete cleanup, and run `git diff --check`.
- Update an existing README when the public API or documented user-facing behavior changes. Do not create new documentation files unless explicitly requested.

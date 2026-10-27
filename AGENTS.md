<required-skills>

- You MUST activate the `engineering-discipline` skill before every repository task that may modify files or public behavior, including implementation, bug fixes, refactors, tests, configuration, assets, documentation, cleanup, and code review.
- `engineering-discipline` is additive: activate every other skill required by the task's domain.
- When available, activate the generated Laravel, Filament, Livewire, Tailwind, and testing skills for relevant work. Repository rules take precedence over upstream examples; run application-specific commands only in a suitable Laravel application.

</required-skills>

# Repository Guidelines

## Scope and Instruction Precedence

- These instructions apply to the monorepo root and every package below it.
- Before repository work, read `AGENTS.local.md` at the monorepo root when present for local environment and integration instructions. Keep private paths and local workflow details in that ignored file.
- Each package under `packages/` is independently releasable. A package's own `AGENTS.md` adds to or overrides these rules for files inside that package.
- Before changing a package, read its `AGENTS.md` when present, the relevant README sections, and `composer.json`. Inspect implementation, tests, entry points, configuration, migrations, assets, and static-analysis setup as relevant to the change.
- Keep shared rules here and package-specific additions in package instructions; avoid copying shared rules into each package.

## Language

- Write repository content in English, including documentation, comments, PHPDoc, test descriptions, TODOs, and developer-facing messages.
- Keep intentional product translations and localized UI copy in their target language.
- Respond to the user in their language. Keep progress updates and final reports concise; explain the outcome, relevant verification, and any remaining blockers.

## Scope and Compatibility

- Keep changes inside the package and its declared dependency boundaries. Consume another package through its public API; do not create reverse or cyclic dependencies.
- All PHPinnacle packages are currently development-only. There are no stability or backward-compatibility guarantees for public APIs, configuration, serialized formats, database schemas, or other package contracts.
- Breaking changes needed for the requested task are allowed. Change the current contract directly and update affected consumers, tests, fixtures, demos, and documentation together. Preserve behavior outside the requested scope.
- Do not add backward-compatibility layers, legacy aliases, deprecation shims, obsolete-format fallbacks, or data upgrade paths unless explicitly requested.
- Maintain package migrations for fresh installations: edit or consolidate existing migrations into the current schema instead of preserving intermediate schema changes or adding data conversion migrations.
- Rewriting migration files does not authorize resetting databases or deleting existing data; those actions require explicit user instruction.
- Do not add dependencies or new top-level directories without approval.
- Keep Laravel package wiring in the service provider and panel wiring in the Filament plugin. These are composition roots, not homes for runtime domain behavior.

## Design and Ownership

- Implement the smallest complete solution for the current requirement. Do not add speculative features, configuration, compatibility layers, or extension points.
- Prefer existing Laravel, Filament, Livewire, and package components over local framework-like abstractions.
- An abstraction must not know or anticipate specific descendants. If its implementation depends on descendant types, names, identifiers, settings, or special cases, remove that abstraction and put the behavior in the concrete implementations. Do not hide the same dependency in traits, flags, registries, or another base class; prefer small, explicit duplication.
- Use arrays for lists, maps, and serialization boundaries. Convert structured domain state into typed value objects before domain behavior begins.
- Use explicit parameter and return types on named methods, constructor property promotion, descriptive names, and PHPDoc array shapes where arrays are intentional.
- Do not declare return types on anonymous functions or arrow functions.
- Let Eloquent models own their persisted state, relationships, lifecycle defaults, model-scoped reads, and cohesive state transitions.
- All package-owned models use the package's configured database connection. Resolve it on the models; do not manually propagate connection names between models, queries, services, or jobs with `setConnection()`, `on()`, or serialized connection fields. Keep explicit connection selection at boundaries that access external data sources.
- Do not define or use Eloquent local scopes. Put reusable model-owned queries in explicit static methods that return typed Eloquent builders. Global scopes are allowed when they apply an invariant to every model query.
- Use services for external APIs, provider protocols, registries, reflection or storage adapters, parsing and formatting, authorization orchestration, and cross-boundary transactions.
- Never store request-, user-, or tenant-specific state in static properties or singleton services under Octane.

## Avoid Contract-Bypassing Workarounds

- A contract-bypassing workaround compensates for another component's limitation or defect by bypassing its public contract, misrepresenting data, or reproducing its internal behavior in a consumer. Avoid these workarounds; fix or extend the component that owns the behavior and consume its public API.
- Do not override another component's internal methods or force its availability, compatibility, or validation results to enable unsupported behavior.
- Do not change a value's meaning, type, or path, introduce dummy values, or discard information merely to pass another component's restrictions.
- Keep general mechanisms independent of particular implementations. Do not branch on a descendant's class, identifier, or implementation-specific fields to supply behavior that belongs to that descendant.
- Do not duplicate another component's parsing, validation, or computation in a consumer when the rules must change together. Keep those rules with their owner and expose the capability through its public contract.
- Ordinary adapters, intentional boundary conversions, documented extension points, and justified workarounds for external library defects are not automatically violations. Judge whether the code preserves the contract and data semantics. For each finding, identify a concrete scenario, the misplaced responsibility, and the smallest correction.

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

## Commit Messages

- Prefix a package-specific commit subject with the package name: `[package]: Some awesome changes`.
- For changes spanning two or three packages, join every package name with `+`: `[package+package+package]: Some awesome changes`.
- For changes spanning four or more packages, use the primary package followed by the total number of affected packages: `[package+5]: Some awesome changes`.

## Working Method and Verification

- Inspect the relevant implementation and tests before choosing a design. Surface assumptions only when they materially affect public contracts, persisted data, authorization, security, or external side effects.
- Make surgical changes: every changed line must serve the requested outcome, its verification, or cleanup made necessary by the change. Preserve unrelated user work and report unrelated defects instead of fixing them.
- Remove only imports, code, tests, assets, and configuration made obsolete by the current change.
- Define observable success before implementation. Reproduce bugs with the narrowest practical test, cover invalid inputs for validation changes, and establish a passing baseline before refactors.
- Keep JavaScript and PHP test suites independently runnable. JavaScript tests must not require PHP, Composer dependencies, or a booted PHP application; PHP tests must not require Node.js, npm dependencies, or JavaScript builds.
- Do not invoke the other language's runtime to generate test inputs or expected results. Define explicit fixtures in the test suite's own language.
- For PHP changes, run `composer format`, focused package tests, and repository-wide `composer lint`. Also run `composer lint` when changing Composer or PHP analysis configuration. Use the package's declared test runner.
- For frontend behavior changes, run relevant JavaScript and integration checks and rebuild affected committed assets as required by the package.
- When changing custom Filament fields or nested schemas, verify the affected hydration, formatting, validation, and dehydration lifecycle through the component's public API. Check state independence between repeated instances when applicable. For browser interaction changes, verify the affected interaction after initial rendering and subsequent Livewire updates.
- For documentation or instruction-only changes, check consistency, referenced commands and links, and skill metadata where applicable; runtime suites are needed only when executable examples or behavior are affected.
- Start with the narrowest relevant checks and complete required checks. Broaden or repeat them only for new changes, failures, or unresolved risks; finish once the requested outcome and verification are complete.
- Finish by inspecting the diff for scope drift, hidden behavior changes, unnecessary complexity, and incomplete cleanup, and run `git diff --check`.
- Update an existing README when the public API or documented user-facing behavior changes. Do not create new documentation files unless explicitly requested.

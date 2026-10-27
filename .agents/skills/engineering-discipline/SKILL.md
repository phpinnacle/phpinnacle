---
name: engineering-discipline
description: Preserve scope, existing user work, and verifiable outcomes during repository changes and code review. Complements simplicity-focused coding skills such as ponytail.
---

# Engineering Discipline

Apply these safeguards alongside the task's domain-specific skills. When ponytail is active, it supplies the simplicity and implementation rules; this skill adds scope control and verification.

## Resolve Consequential Uncertainty

- Resolve routine, reversible decisions from inspected code, local conventions, and the user's instructions; proceed without unnecessary confirmation.
- State consequential assumptions. Ask only when missing information or authority materially affects scope, public contracts, persisted data, security, or external side effects and cannot be resolved from available context. Continue independent work while waiting.

## Assess Ownership and Execution Boundaries

- Before a non-trivial change, identify the source of truth, the owner of each affected rule, and consumers that reconstruct that rule. Resolve ownership before choosing local edits. Keep this assessment brief and specific to the task.
- For changes that write data, dispatch work, or expose an action, identify the database connection, transaction boundary, execution context, and authorization decision as applicable. Verify the affected boundary directly.

## Keep the Diff Scoped

- Minimize the complete implementation required by the behavior. A smaller patch is not preferable when it preserves duplicated rules or contract-bypassing workarounds.
- Every changed line must serve the requested outcome, its verification, or cleanup made necessary by the change.
- Preserve unrelated user changes and local style. Do not reformat or refactor adjacent code; report unrelated defects or dead code instead of changing them.
- Remove imports, code, tests, assets, and configuration made obsolete by this change. Leave pre-existing unrelated cleanup alone.

## Verify the Outcome

- Define observable success before editing. For substantial work, give a brief plan tied to the checks that establish completion.
- For bugs, reproduce the failure with the narrowest practical regression test before fixing it when feasible. For behavioral refactors, establish a passing baseline when feasible. Test validation at its owning boundary.
- For each new regression test, name the observable failure it detects. Prefer assertions about returned values, persisted state, authorization, and side effects. Assert markup, call order, or computation counts when those details are part of the required behavior.
- Use existing checks that verify observable behavior or meaningful invariants. Keep rigor proportional to risk; do not add tests that merely mirror implementation or cover trivial copy and formatting changes.
- Run the repository's required checks for the changed file types, starting with focused verification. Broaden or repeat checks only for new changes, failures, or unresolved risks.
- Inspect the final diff for scope drift, unintended behavior changes, and incomplete cleanup. Finish when the requested outcome and required verification are complete.
- Report checks that could not run and what remains unverified. Distinguish pre-existing failures from regressions caused by the change, and compiled SQL or configuration checks from execution against the real system.

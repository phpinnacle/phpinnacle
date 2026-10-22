---
name: branch-refactor
description: >
  Refactor the current branch in this repository through four sequential stages
  in fresh subagents: PHP, JavaScript, tests, and documentation. Use for a
  comprehensive branch refactor, rather than a focused fix or review-only request.
---

# Branch Refactor

Simplify the branch while preserving observable behavior, public APIs, data formats, validation, and error handling. Follow applicable `AGENTS.md` instructions and activate [engineering-discipline](../engineering-discipline/SKILL.md) and required domain skills.

## Establish Scope

The main agent owns scope, coordination, and the final result.

- Identify the base branch from the user's request or repository evidence. Use its merge base with `HEAD` to identify branch changes. Ask only if the base remains ambiguous and would materially change scope.
- Inspect the working tree and distinguish existing local work from branch commits. Preserve unrelated changes; do not treat their presence as permission to refactor them.
- Identify affected packages, entry points, callers, tests, fixtures, configuration, assets, and documentation. Expand beyond branch changes only where necessary to complete the refactor.
- Establish the relevant verification baseline before editing. Record existing failures and unavailable checks so later stages can distinguish them from regressions.
- Define success as simpler ownership and structure with preserved contracts and passing required checks. Do not require a minimum number of edits or abstractions removed.

Report discovered behavior bugs separately unless fixing them is already authorized. A refactor does not authorize changing intended behavior.

## Delegate Sequentially

Run each stage in a separate new subagent with no inherited conversation history (`fork_turns: "none"`). Do not run stages concurrently. If delegation is unavailable, report the limitation instead of silently substituting a single-agent workflow.

Give each subagent a self-contained task containing:

- Repository path, stage objective, base branch and merge-base commit, and affected packages and paths.
- The requirement to read applicable `AGENTS.md` files and activate mandatory skills.
- Observable contracts to preserve and any authorized behavior changes.
- Existing local changes to preserve, baseline check results, and factual handoffs from completed stages.
- The requirement to inspect the current implementation and tests, complete related changes, and return verification evidence.

After each stage, inspect its diff and results before starting the next. Resolve regressions introduced by the stage first. Existing failures or unavailable checks may remain only with their impact on verification explained. A stage with no justified changes should report that outcome without manufacturing edits.

Each handoff must list changed files, the purpose of the changes, checks run and their results, and unresolved issues. Pass this information in the next task; no handoff files are required.

## Stages

### 1. PHP

Inspect the changed PHP code and necessary related code. Simplify unnecessary layers, misplaced responsibilities, contract-bypassing workarounds, and duplication of rules that must change together. Keep similar code separate when its responsibilities differ.

Fix the cause in the component that owns the behavior and consume its public API. Do not replace explicit code with speculative abstractions. Update affected callers, tests, and documentation as required by the change, and complete the repository's PHP checks before handing off.

### 2. JavaScript

Apply the same ownership and simplification criteria to JavaScript. Inspect PHP integration, component lifecycle, and affected user interactions. Complete relevant tests, integration checks, committed asset builds, and playground updates required by the affected packages.

### 3. Tests

Simplify tests and fixtures within the established scope. Test business rules, component APIs, and observable outcomes. Remove coupling to private structure, incidental call order, or source text when those details are not part of the contract.

Retain distinct scenarios, meaningful regression coverage, and checks at validation and security boundaries. Use mocks where they isolate a real boundary; do not remove them categorically. Prefer assertions about results and side effects over recreating the implementation. Keep PHP and JavaScript suites independently runnable, and run the affected suites after changes.

This stage improves test design; earlier stages must already include the tests needed to verify their changes.

### 4. Documentation

Compare documentation for affected features with the final implementation. Correct API descriptions, examples, commands, and links. Remove repetition and simplify structure and wording while retaining information needed to use the packages correctly.

Update existing documentation. Create new documentation files only when explicitly requested. This stage completes the documentation review; required documentation updates should not be postponed from earlier stages.

## Finish

Inspect the aggregate diff against the original scope and working-tree baseline. Check for unintended behavior changes, unnecessary abstractions, incomplete cleanup, and cross-package boundary violations. Run remaining required checks and `git diff --check`; repeat completed checks only when subsequent changes affect their validity.

Report the outcome of each stage, verification results, and remaining issues or unverified behavior. Do not present unavailable checks as passing.

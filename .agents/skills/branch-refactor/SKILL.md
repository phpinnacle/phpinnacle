---
name: branch-refactor
description: >
  Refactor the current branch in this repository through sequential stages
  in fresh subagents: PHP and JavaScript/TypeScript when in scope, followed by
  mandatory tests and documentation reviews. Use for a
  comprehensive branch refactor, rather than a focused fix or review-only request.
---

# Branch Refactor

Simplify the affected functionality while preserving intended product behavior, validation guarantees, and error handling. Follow applicable `AGENTS.md` instructions and activate [engineering-discipline](../engineering-discipline/SKILL.md) and required domain skills.

Public APIs, internal interfaces, configuration, and data formats may change when needed for a simpler design. Update all affected consumers, tests, fixtures, demos, and documentation together; do not add compatibility layers. Follow repository migration rules and preserve existing data; contract changes do not authorize database resets or data deletion.

Optimize for the simplest complete design of the affected functionality. The current layers, interfaces, file layout, and smallest possible diff are not constraints when they prevent that outcome. Keep changes cohesive and leave unrelated functionality and user work alone.

## Establish Scope

The main agent owns scope, coordination, and the final result.

- Inspect the working tree first. If uncommitted changes exist, use their files as entry points, including staged, unstaged, and untracked files. Preserve the intent of the existing work.
- If the working tree is clean, use all changes in the current branch relative to the base branch as entry points. Identify the base from the user's request or repository evidence and compare `HEAD` with their merge base. Ask only if the base remains ambiguous and would materially change scope.
- Define scope by the functionality affected by those entry points. Trace its models, services, entry points, callers, tests, fixtures, configuration, assets, and documentation, including unchanged files. Inspect and improve their ownership and interactions as a whole rather than limiting refactoring to changed lines or files.
- Record the original entry points, functional scope, and working-tree baseline before editing. Carry them across stages instead of recalculating scope from the evolving diff. Extend the functional scope when inspection reveals a necessary dependency or consumer, and explain the connection in the next handoff; do not expand into unrelated package cleanup.
- Establish the relevant verification baseline before editing. Record existing failures and unavailable checks so later stages can distinguish them from regressions.
- Define success as simpler ownership and structure with preserved product behavior, consistently updated contracts, and passing required checks. Do not require a minimum number of edits or abstractions removed.

Report discovered behavior bugs separately unless fixing them is already authorized. A refactor does not authorize changing intended behavior.

## Analyze Before Choosing Edits

Before selecting edits, each PHP and JavaScript/TypeScript stage must briefly assess the functional design from the implementation, consumers, and tests:

- Identify the source of truth for domain state and the components that own its rules. Point out consumers that reconstruct behavior belonging to another owner.
- Identify duplicated state, derived data stored alongside its inputs, repeated computations, and rules that must change together. Distinguish shared rules from similar code with different responsibilities.
- Compare local simplification with changing the affected model or contract. Name the concrete complexity each option removes, the consumers it reaches, and any behavior or data semantics that must be preserved.

Choose the simplest complete design based on this assessment, then implement it. Do not stop at helper inlining or cosmetic cleanup when an identified ownership or contract problem remains. If no structural change is justified, explain why; do not invent alternatives or force a redesign. Include the assessment and chosen approach in the stage handoff, without creating a separate report file.

## Delegate Sequentially

Run each applicable stage in a separate new subagent with no inherited conversation history (`fork_turns: "none"`). Before spawning PHP or JavaScript/TypeScript agents, assess whether their changes are in scope using the criteria below; skip out-of-scope stages without spawning agents. Always run the tests and documentation stages to review the affected features and seek useful improvements, even when their files were not initially changed. Do not run stages concurrently. If delegation is unavailable, report the limitation instead of silently substituting a single-agent workflow.

Give each subagent a self-contained task containing:

- Repository path, stage objective, original entry points (uncommitted files or branch changes), functional scope, affected packages and paths, and base branch and merge-base commit when using branch changes.
- The requirement to read applicable `AGENTS.md` files and activate mandatory skills.
- Product behavior, validation guarantees, and error handling to preserve, contract changes and affected consumers, and any separately authorized behavior changes.
- Existing local changes to preserve, baseline check results, and factual handoffs from completed stages.
- The requirement to inspect the current implementation and tests, complete the design assessment before choosing implementation edits, complete related changes, and return verification evidence.

After each stage, inspect its diff and results before starting the next. Resolve regressions introduced by the stage first. Existing failures or unavailable checks may remain only with their impact on verification explained. A stage with no justified changes should report that outcome without manufacturing edits.

Each handoff must list changed files, the purpose of the changes, checks run and their results, and unresolved issues. Pass this information in the next task; no handoff files are required.

## Stages

### 1. PHP (when in scope)

Run this stage only when the functional scope includes affected PHP code, including Blade templates, PHP tests, migrations, or PHP configuration. The mere presence of PHP in an affected package is not enough.

Inspect PHP implementation throughout the functional scope, including unchanged owners and consumers. Simplify unnecessary layers, misplaced responsibilities, contract-bypassing workarounds, and duplication of rules that must change together. Keep similar code separate when its responsibilities differ.

Fix the cause in the component that owns the behavior and consume its public API. Do not replace explicit code with speculative abstractions. Update affected callers, tests, and documentation as required by the change, and complete the repository's PHP checks before handing off.

### 2. JavaScript/TypeScript (when relevant)

Run this stage only when the functional scope includes affected JavaScript/TypeScript code or its tests, fixtures, build configuration, assets, or frontend integration (including PHP and Blade). The mere presence of JavaScript/TypeScript in an affected package is not enough.

Apply the same ownership and simplification criteria throughout the JavaScript/TypeScript functional scope, including unchanged owners and consumers. Inspect PHP integration, component lifecycle, and affected user interactions. Complete relevant tests, integration checks, committed asset builds, and playground updates required by the affected packages.

### 3. Tests

Simplify tests and fixtures within the established scope. Test business rules, component APIs, and observable outcomes. Remove coupling to private structure, incidental call order, or source text when those details are not part of the contract.

Retain distinct scenarios, meaningful regression coverage, and checks at validation and security boundaries. Use mocks where they isolate a real boundary; do not remove them categorically. Prefer assertions about results and side effects over recreating the implementation. Keep PHP and JavaScript suites independently runnable, and run the affected suites after changes.

This stage improves test design; earlier stages must already include the tests needed to verify their changes.

### 4. Documentation

Compare documentation for affected features with the final implementation. Correct API descriptions, examples, commands, and links. Remove repetition and simplify structure and wording while retaining information needed to use the packages correctly.

Update existing documentation. Create new documentation files only when explicitly requested. This stage completes the documentation review; required documentation updates should not be postponed from earlier stages.

## Finish

Inspect the aggregate diff against the functional scope, explained scope extensions, and working-tree baseline. Check for unintended product behavior changes, incomplete consumer updates, unnecessary abstractions, incomplete cleanup, and cross-package boundary violations. Run remaining required checks and `git diff --check`; repeat completed checks only when subsequent changes affect their validity.

Report the outcome of each stage (including the reason for any skipped stage), contract changes, verification results, and remaining issues or unverified behavior. Do not present unavailable checks as passing.

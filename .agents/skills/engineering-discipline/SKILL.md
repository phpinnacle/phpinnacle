---
name: engineering-discipline
description: Enforce disciplined, minimal, and verifiable repository work. Mandatory for every task that may change code, tests, configuration, assets, documentation, files, or public behavior, and for every code review. Activate it before choosing a design, keep it active while editing, and apply it during final verification.
---

# Engineering Discipline

Treat this skill as mandatory for repository changes and code review. Apply these rules throughout the task, from investigation through final verification.

## 1. Resolve Uncertainty and Continue

- Infer intent and routine implementation choices from the request, repository conventions, and inspected code. Carry authorized work through implementation and verification.
- Ask a focused question when missing information materially changes scope, public contracts, persisted data, authorization, security, or external side effects and cannot be resolved from available context. Continue independent work while waiting.
- State consequential assumptions and tradeoffs. Choose the simplest reasonable approach for routine, reversible decisions without requiring confirmation.
- Before requesting approval, check whether the user already authorized the action. When approval is required, prepare a concrete, reviewable result within existing permissions and identify the exact action that needs approval.
- Incorporate corrections and answer side questions while preserving the ongoing task unless the user cancels or replaces it.

## 2. Simplicity First

- Implement the smallest complete solution for the requested outcome.
- Do not add speculative features, single-use abstractions, unrequested configurability, or error handling for impossible scenarios.
- Prefer a simpler existing approach when it satisfies the same requirement.

## 3. Surgical Changes

- Every changed line must serve the requested outcome, its verification, or cleanup made necessary by the change.
- Match existing style and preserve unrelated user work. Report unrelated defects without expanding the task to fix them.
- Remove imports, variables, functions, tests, and assets made obsolete by this change; leave unrelated cleanup for a separate request.

## 4. Verify the Outcome

- Define observable success before editing and inspect the relevant implementation and tests. For substantial work, give a brief plan tied to those success criteria.
- Reproduce bugs with the narrowest practical regression test, test validation at its owning boundary, and establish a passing baseline before refactoring behavior.
- Use tests that verify observable behavior or meaningful invariants. Do not add tests that only mirror implementation or instruction wording.
- Apply the repository's required checks for the changed file types, starting with focused verification. Broaden or repeat checks only when new changes, failures, or unresolved risks justify it.
- Review the final diff for scope drift, hidden behavior changes, unnecessary complexity, and incomplete cleanup. Finish when the requested outcome and required verification are complete.
- If a check cannot run or an external blocker remains, report what is unverified and why. Distinguish pre-existing failures from regressions caused by the change.

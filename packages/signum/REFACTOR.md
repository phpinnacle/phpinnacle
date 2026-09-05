# Refactor plan

Reviewed against the working tree on 2026-09-05. No structural refactor from the previous list currently has enough benefit to justify implementation.

## Useful next work: cover the public lifecycle

`tests/Unit/LevelTest.php` only checks enum presentation. Before changing the model/actions, cover `Alert::create()`, target-specific `list()`, dismissal, and the configured connection with database/Filament integration tests.

Acceptance: creation assigns the correct morph target and current creator, two target types sharing an ID remain separate, `list(null)` remains empty, and allowed/denied action execution respects the resource policy. Verify actual execution rather than only button visibility. Add guest/queue creation coverage if that path is changed, preserving the current nullable creator behavior.

Any authorization or persistence defect discovered by these tests should become a focused behavior-fix item with a reproducer.

## Removed from the active queue

- Wrapping the five-line `Alert::create(Model, array)` body in another method adds no ownership boundary. Target assignment and persistence already belong to the model.
- Moving `Auth::id()` out of the creation event changes behavior for direct model callers and queues. An explicit-actor API should wait for a concrete use case and compatibility decision; a facade-free model is not the goal.
- A `forTarget()` scope has only one current query consumer. Do not add it for hypothetical future operations; extract when another real query needs the same predicate.

Keep the current public model API, model-owned lifecycle defaults, and Filament action wiring.

# Refactor plan

Reviewed against the working tree on 2026-09-05. Preserve versioned document state, descriptor contracts, lexical scope, editor command payloads, and generated Twig. Implement one boundary at a time.

## 1. Priority: medium — consolidate filter form mapping with explicit capabilities

`TwigTemplatePlugin` repeats catalog lookup and `{name, arguments, configuration}` construction in `normalizeBlockFilters()`, `normalizeCollectionFilters()`, and `normalizeFilters()`. `normalizeConditionFilters()` already delegates to the variable path and adds a restriction; there is no separate loop normalizer to extract.

Share the repeated mapping while keeping block support, collection-preserving output, variable type support, and condition type preservation explicit. Use the existing `Filter` contracts and argument conversion; do not introduce a second catalog or revalidate trusted descriptor callback results.

Acceptance: action tests cover ordered filters and configured arguments for variables, apply blocks, block/row/cell loops, and condition operands. Invalid browser selections still produce the intended translated form errors, while validated form data is consumed directly.

## 2. Priority: medium, conditional — separate condition mapping from action setup

The form-to-AST helpers are small; move them together only if they can form a cohesive mapper used by insertion and editing without pulling in the full plugin. Keep action schemas and Livewire command dispatch in `TwigTemplatePlugin`, and compilation in the existing `ConditionExpressionSerializer`.

Acceptance: comparison/test/truthy clauses, negation, literal types, filtered operands, and nested loop variables retain the same AST and validation behavior. Exercise actual action-to-command payloads in addition to existing serializer tests.

## 3. Priority: medium — isolate serializer algorithms incrementally

`TwigDocumentSerializer` contains substantial table-loop/row-condition and paired-inline-condition algorithms. Extract one cohesive algorithm first, behind `serialize()`, without creating a generic node-handler registry or duplicating recursion/escaping in every collaborator.

Acceptance: `TwigDocumentSerializerTest` retains exact output for nested tables, row/cell loops, row conditions, paired/nested inline branches, and escaping. Keep current rejection behavior at the user document boundary; do not add compatibility formats or repair trusted stored state during this change.

## Missing from the previous plan: browser-side verification

`resources/js/twig-rich-editor.js` owns the corresponding ProseMirror nodes and commands. PHP tests and an asset build do not prove selection, undo, or panel synchronization works. For each affected flow, run a browser smoke check covering insertion/editing, keep-content/delete, undo/redo, nested scope, and save/reload; build through the existing `npm run build` command when assets change.

Split JavaScript only along a concrete command/node boundary needed by that work. Keep source and distributed assets aligned and preserve the field's existing loading mechanism. A broad PHP/JavaScript rewrite or new dependency is outside this plan.

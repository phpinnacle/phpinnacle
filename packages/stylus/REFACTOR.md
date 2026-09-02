# Refactor

Only local, behavior-preserving cleanup is listed here. Public API changes and package-wide redesigns are intentionally excluded.

## 1. Extract condition form mapping

Move the condition form-to-AST and argument-to-operand methods from `TwigTemplatePlugin` into a focused mapper, while the plugin continues to configure Filament actions.

## 2. Extract filter normalization

Move the block, collection, condition, loop, and variable filter normalization methods from `TwigTemplatePlugin` into one catalog-aware normalizer used by the existing actions.

## 3. Split serializer concerns behind the existing facade

Move table serialization and inline-condition serialization out of `TwigDocumentSerializer` into internal collaborators, keeping `serialize()` and the generated Twig output unchanged.

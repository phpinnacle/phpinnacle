# Ideas

Only small, additive features are listed here. Refactors and package-wide redesigns are intentionally excluded.

## 1. Required object properties

Translate an OpenAPI object's `required` array into Laravel rules for both top-level and nested properties.

## 2. Array item rules

Generate wildcard validation rules from `items` and support `minItems`, `maxItems`, and `uniqueItems`.

## 3. Local reference resolution

Resolve local component references such as `#/components/schemas/Address` before producing rules, with a clear error for missing or circular references.

# PHPinnacle Grimoire

Grimoire provides project-specific linter rules and analyzer extensions for [Mago](https://mago.carthage.software/).

## Installation

```bash
composer require --dev phpinnacle/grimoire
```

Extend the bundled Mago configuration from your project's `mago.toml`:

```toml
extends = "vendor/phpinnacle/grimoire/mago.toml"
```

Relative configuration paths are resolved from the worker's working directory, which defaults to the directory containing `mago.toml`.

Create `grimoire.json`:

```json
{
    "rules": {
        "class-member-order": [
            {
                "order": [
                    "trait-use:*",
                    "enum-case:*",
                    "public:constant:*",
                    "protected:constant:*",
                    "private:constant:*",
                    "public:static:property:*",
                    "protected:static:property:*",
                    "private:static:property:*",
                    "public:property:*",
                    "protected:property:*",
                    "private:property:*",
                    "method:__construct",
                    "method:*"
                ]
            }
        ],
        "class-method-order": [
            {
                "extends": "Illuminate\\Database\\Migrations\\Migration",
                "order": [
                    "up",
                    "down",
                    "getConnection",
                    "public:static:*",
                    "public:*",
                    "protected:*",
                    "private:*"
                ]
            },
            {
                "order": [
                    "__construct",
                    "public:static:*",
                    "public:*",
                    "protected:*",
                    "private:*",
                    "protected:static:*",
                    "private:static:*"
                ]
            }
        ],
        "compact-empty-method-body": {
            "method-pattern": "__construct"
        }
    }
}
```

## Rules

### `phpinnacle/blank-line-before-control-structure`

Requires a blank line before `if`, `for`, `foreach`, `while`, `do`, `switch`, and `try` when they follow another statement in the same block. The first statement in a block does not need a leading blank line. Continuation clauses (`else`, `elseif`, `catch`, `finally`, and the closing `while` of `do ... while`) are unaffected. Alternative syntax is supported.

Leading comments stay with the control structure, and trailing comments stay with the preceding statement. The rule is enabled by default and requires no configuration. Apply its safe fixes with:

```bash
mago lint --fix --format-after-fix --only phpinnacle/blank-line-before-control-structure
```

### `phpinnacle/class-member-order`

Orders trait uses, enum cases, constants, properties, and methods using the first matching configuration in the `class-member-order` array. The optional `extends` glob matches the resolved direct parent class; omit it to match every class-like declaration.

Selectors use the form `[modifier:...:]type:name-pattern`. Supported types are `trait-use`, `enum-case`, `constant`, `property`, and `method`. For example, `public:static:property:cache*` matches public static properties whose names begin with `cache`, while `method:__construct` matches constructors. A grouped property, constant, or trait-use declaration matches when any declared name matches. Members unmatched by any selector remain last in their original order. Comments and attributes move with their member.

### `phpinnacle/class-method-order`

Orders methods in classes, anonymous classes, traits, interfaces, and enums using the first matching configuration in the `class-method-order` array. The optional `extends` glob matches a resolved direct parent; omit it to match every class-like declaration. Put specific configurations, such as migrations, before the general order.

Selectors are evaluated from top to bottom. A bare selector is a method-name glob; prefixes require matching modifiers. Selectors without `static` match only instance methods: `public:*` excludes static methods, while `public:static:*` selects public static methods. Methods in the same group retain their original order, without alphabetic sorting. Unmatched methods remain last in their original order. Keep the formatter's `sort-class-methods` disabled when using a custom order.

### `phpinnacle/compact-empty-method-body`

Requires empty method bodies to use compact braces (`{}`). Bodies containing comments are not considered empty.

By default, the rule applies to every method. Restrict it with the `method-pattern` glob in `grimoire.json`.

Apply the safe fixes with:

```bash
mago lint --fix --only phpinnacle/class-member-order,phpinnacle/class-method-order,phpinnacle/compact-empty-method-body
```

### `phpinnacle/no-closure-return-type`

Disallows return type declarations on anonymous functions and arrow functions, including static variants. Named functions and methods are unaffected. The rule is enabled by default and requires no configuration.

```php
$callback = function (string $value) {
    return trim($value);
};
$mapper = static fn (string $value) => trim($value);
```

The automatic fix removes the return type declaration and preserves comments. It is classified as unsafe because removing a return type can change runtime behavior. Apply it with:

```bash
mago lint --fix --unsafe --format-after-fix --only phpinnacle/no-closure-return-type
```

## Testing

```bash
composer test
```

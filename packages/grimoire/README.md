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
                    "public:static:method:*",
                    "public:method:*",
                    "protected:static:method:*",
                    "protected:method:*",
                    "private:static:method:*",
                    "private:method:*"
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
            }
        ],
        "compact-empty-method-body": {
            "method-pattern": "__construct"
        }
    }
}
```

## Rules

### `phpinnacle/class-member-order`

Orders trait uses, enum cases, constants, properties, and methods using the first matching configuration in the `class-member-order` array. The optional `extends` glob matches the resolved direct parent class; omit it to match every class-like declaration.

Selectors use the form `[modifier:...:]type:name-pattern`. Supported types are `trait-use`, `enum-case`, `constant`, `property`, and `method`. For example, `public:static:property:cache*` matches public static properties whose names begin with `cache`, while `method:__construct` matches constructors. A grouped property, constant, or trait-use declaration matches when any declared name matches. Members unmatched by any selector remain last in their original order. Comments and attributes move with their member.

### `phpinnacle/class-method-order`

Orders class methods using the first matching configuration in the `class-method-order` array. The optional `extends` glob matches the resolved direct parent class; omit it to match any class.

Selectors are evaluated from top to bottom. A bare selector is a method-name glob; prefixes require matching modifiers, so `public:static:*` matches every public static method. Methods unmatched by any selector remain last in their original order.

### `phpinnacle/compact-empty-method-body`

Requires empty method bodies to use compact braces (`{}`). Bodies containing comments are not considered empty.

By default, the rule applies to every method. Restrict it with the `method-pattern` glob in `grimoire.json`.

Apply the safe fixes with:

```bash
mago lint --fix --only phpinnacle/class-member-order,phpinnacle/class-method-order,phpinnacle/compact-empty-method-body
```

## Testing

```bash
composer test
```

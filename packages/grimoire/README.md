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

The bundled configuration reports `literal-named-argument` only when a call contains at least two literal positional arguments.

Create `grimoire.json`:

```json
{
    "rules": {
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

### `phpinnacle/class-method-order`

Orders class methods using the first matching configuration in the `class-method-order` array. The optional `extends` glob matches the resolved direct parent class; omit it to match any class.

Selectors are evaluated from top to bottom. A bare selector is a method-name glob; prefixes require matching modifiers, so `public:static:*` matches every public static method. Methods unmatched by any selector remain last in their original order.

### `phpinnacle/compact-empty-method-body`

Requires empty method bodies to use compact braces (`{}`). Bodies containing comments are not considered empty.

By default, the rule applies to every method. Restrict it with the `method-pattern` glob in `grimoire.json`.

Apply the safe fixes with:

```bash
mago lint --fix --only phpinnacle/class-method-order,phpinnacle/compact-empty-method-body
```

## Testing

```bash
composer test
```

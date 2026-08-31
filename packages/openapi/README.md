# PHPinnacle OpenAPI

[![Latest Version on Packagist](https://img.shields.io/packagist/v/phpinnacle/openapi.svg?style=flat-square)](https://packagist.org/packages/phpinnacle/openapi)
[![Total Downloads](https://img.shields.io/packagist/dt/phpinnacle/openapi.svg?style=flat-square)](https://packagist.org/packages/phpinnacle/openapi)

PHPinnacle OpenAPI bridges OpenAPI schema objects and Laravel validation. Its `RuleFactory` recursively converts an `OpenApi\Attributes\Schema` into a Laravel rules array that can be extended with application-specific rules.

## Requirements

- PHP 8.4 or later
- Laravel 13
- `zircote/swagger-php`

## Installation

```bash
composer require phpinnacle/openapi
```

The service provider is discovered automatically. The package has no migrations, views or frontend assets.

## Generating validation rules

Resolve `RuleFactory` through dependency injection and pass an OpenAPI schema:

```php
use OpenApi\Attributes as OA;
use PHPinnacle\OpenApi\Validation\RuleFactory;

final class StoreOrderRules
{
    public function __construct(private RuleFactory $rules) {}

    public function rules(): array
    {
        $schema = new OA\Schema(
            required: ['email'],
            properties: [
                new OA\Property(property: 'email', type: 'string', format: 'email'),
                new OA\Property(property: 'quantity', type: 'integer', minimum: 1),
            ],
            type: 'object',
        );

        return $this->rules->make($schema, [
            'email' => ['unique:users,email'],
        ]);
    }
}
```

The second argument supplies extra Laravel rules keyed by attribute. Use the optional third `prefix` argument when the schema belongs below a nested request key.

```php
$rules = $factory->make($schema, [], 'payload');
```

The result can be returned from a Form Request's `rules()` method or passed directly to `Validator::make()`.

### Validating `oneOf` objects

For a prefixed object schema, `RuleFactory` can enforce that exactly one `oneOf` branch matches. Each branch declares the local properties that select it through `required`:

```php
$schema = new OA\Schema(
    properties: [
        new OA\Property(property: 'card', type: 'object'),
        new OA\Property(property: 'token', type: 'string'),
    ],
    oneOf: [
        new OA\Schema(required: ['card']),
        new OA\Schema(required: ['token']),
    ],
    type: 'object',
);

$rules = $factory->make($schema, [], 'payment');
```

The prefixed object remains optional unless the caller adds a Laravel presence rule for it. If present, its value must match exactly one branch.

## Testing

```bash
composer test
```

## Changelog

See [CHANGELOG](CHANGELOG.md) for recent changes.

## License

The MIT License (MIT). See [License File](LICENSE.md).

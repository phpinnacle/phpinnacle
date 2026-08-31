# Money for Laravel, Livewire, and Filament

`phpinnacle/money` provides an integer-based Money value object together with currency metadata, locale-aware formatting, Eloquent attributes, Laravel validation, Livewire hydration, and Filament fields.

## Features

- Monetary amounts stored as integer minor units.
- ISO 4217 currency validation, names, symbols, and fraction digits.
- Parsing and formatting for decimal input.
- Addition, subtraction, multiplication, percentages, allocation, and comparisons.
- Eloquent multi-column `Attribute` helper.
- JSON, Wireable, and Livewire synthesizer support.
- Filament `MoneyInput`, `CurrencyPicker`, `MoneyColumn`, and `MoneyRangeFilter`.
- English, Polish, and Russian translations and money formatting.

## Installation

```bash
composer require phpinnacle/money
```

Laravel discovers `MoneyServiceProvider` automatically. The provider registers the Livewire synthesizer, views, translations, and built-in formatters. The package has no migrations, configuration, or frontend assets.

## Creating and calculating money

```php
use PHPinnacle\Money\Money;

$price = Money::parse('19.90', 'USD');
$total = $price
    ->mul(2)
    ->add(Money::parse('5.00', 'USD'));

$total->amount;   // 4480
$total->decimal(); // "44.80"
$total->format();
```

Operations combining Money values require matching currencies unless the other amount is zero.

## Eloquent integration

Store the amount and currency in separate columns:

```php
use Illuminate\Database\Eloquent\Casts\Attribute;
use PHPinnacle\Money\Money;

protected function price(): Attribute
{
    return Money::attribute('price', 'currency');
}
```

The model property is hydrated as `Money`, while writes update both columns.

## Filament integration

```php
use PHPinnacle\Money\Forms\CurrencyPicker;
use PHPinnacle\Money\Forms\MoneyInput;
use PHPinnacle\Money\Tables\MoneyColumn;

MoneyInput::make('price')
    ->currencies(['USD', 'EUR'], 'USD')
    ->required();

CurrencyPicker::make('currency');

MoneyColumn::make('price');
```

`MoneyInput` dehydrates to a `Money` instance, and `MoneyColumn` formats one using its currency and subunit. Livewire can bind nested `amount` and `currency` properties through the registered synthesizer.

## Validation

```php
use PHPinnacle\Money\Rules\CurrencyCode;
use PHPinnacle\Money\Rules\MoneyRule;

return [
    'currency' => [new CurrencyCode],
    'maximum' => ['required'],
    'amount' => [MoneyRule::lte('maximum')],
];
```

## Testing

```bash
composer test
```

## License

The MIT License (MIT). See [License File](LICENSE.md).

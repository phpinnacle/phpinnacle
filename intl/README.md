# PHPinnacle Intl

`phpinnacle/intl` provides Symfony Intl-backed country, language, locale, and timezone dictionaries together with Laravel validation and Filament components.

## Installation

```bash
composer require phpinnacle/intl
```

Laravel discovers the package provider and translations automatically.

## Dictionaries

```php
use PHPinnacle\Intl\Countries;
use PHPinnacle\Intl\Languages;
use PHPinnacle\Intl\Locales;
use PHPinnacle\Intl\Timezones;

Countries::list();
Countries::name('PL');
Languages::exists('eng');
Locales::default();
Timezones::name('Europe/Warsaw');
```

Country codes are ISO 3166-1 alpha-2. Language codes use ISO 639-2 alpha-3 values. The synthetic `Countries::OTHER`, `Languages::OTHER`, and `Locales::OTHER` values are supported.

## Validation

```php
use PHPinnacle\Intl\Rules\CountryCode;
use PHPinnacle\Intl\Rules\LanguageCode;

return [
    'country' => [new CountryCode],
    'language' => [new LanguageCode],
];
```

## Filament

```php
use PHPinnacle\Intl\Filters\CountryFilter;
use PHPinnacle\Intl\Forms\CountryPicker;
use PHPinnacle\Intl\Forms\LanguagePicker;
use PHPinnacle\Intl\Forms\LocalePicker;
use PHPinnacle\Intl\Forms\TimezonePicker;

CountryPicker::make('country');
LanguagePicker::make('language');
LocalePicker::make('locale');
TimezonePicker::make('timezone');
CountryFilter::make();
```

## License

The MIT License (MIT). See [License File](LICENSE.md).

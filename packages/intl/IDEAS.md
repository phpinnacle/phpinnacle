# Ideas

Only small, additive features are listed here. Refactors and package-wide redesigns are intentionally excluded.

## 1. Locale and timezone validation rules

Add `LocaleCode` and `TimezoneCode` rules alongside the existing country and language rules, backed by the same Symfony Intl data.

## 2. Timezone offsets in the picker

Group timezones by region and optionally show each zone's current UTC offset, calculated for a caller-supplied date to account for daylight saving time.

## 3. Searchable country flags and codes

Let `CountryPicker` display flag emoji and match searches against both localized country names and ISO codes.

<?php

namespace PHPinnacle\Common\TaxNumber\Countries;

use PHPinnacle\Common\TaxNumber\Country;
use PHPinnacle\Common\TaxNumber\LookupFailure;
use PHPinnacle\Common\TaxNumber\LookupResult;

final class UnitedStates implements Country
{
    public function code(): string
    {
        return 'US';
    }

    public function normalize(string $number): string
    {
        return str_replace([' ', '-'], '', trim($number));
    }

    public function validate(string $number): bool
    {
        return (bool) preg_match('/\A(?!0{9}\z)[0-9]{9}\z/', $number);
    }

    public function lookup(string $number): LookupResult
    {
        return LookupResult::failure(LookupFailure::Unavailable);
    }
}

<?php

use PHPinnacle\Common\TaxNumber\Countries\Poland;
use PHPinnacle\Common\TaxNumber\Countries\Russia;
use PHPinnacle\Common\TaxNumber\Countries\UnitedKingdom;
use PHPinnacle\Common\TaxNumber\Countries\UnitedStates;
use PHPinnacle\Common\TaxNumber\Country;

it('accepts the supported identifier formats and canonicalizes presentation', function (
    Country $country,
    string $input,
    string $canonical,
) {
    expect($country->normalize($input))->toBe($canonical);
    expect($country->validate($canonical))->toBeTrue();
})->with([
    'Polish NIP' => [new Poland, '5260250274', '5260250274'],
    'formatted Polish NIP' => [new Poland, ' pl 526-025-02-74 ', '5260250274'],
    'UK VAT' => [new UnitedKingdom, '553557881', '553557881'],
    'formatted UK VAT' => [new UnitedKingdom, ' gb 553 5578 81 ', '553557881'],
    'UK VAT branch' => [new UnitedKingdom, 'GB146295999727', '146295999727'],
    'US EIN' => [new UnitedStates, '123456789', '123456789'],
    'formatted US EIN' => [new UnitedStates, ' 12-3456789 ', '123456789'],
    'legacy EIN prefix' => [new UnitedStates, '07-1234567', '071234567'],
    'Russian company INN' => [new Russia, ' 7707083893 ', '7707083893'],
    'Russian personal INN' => [new Russia, '784806113663', '784806113663'],
]);

it('rejects invalid lengths, characters, and NIP or INN checksums', function (Country $country, string $number) {
    expect($country->validate($country->normalize($number)))->toBeFalse();
})->with([
    'NIP length' => [new Poland, '526025027'],
    'NIP checksum' => [new Poland, '5260250275'],
    'NIP remainder ten' => [new Poland, '0200000000'],
    'NIP zeros' => [new Poland, '0000000000'],
    'NIP wrong prefix' => [new Poland, 'GB5260250274'],
    'NIP letters' => [new Poland, '526O250274'],
    'VAT too short' => [new UnitedKingdom, '12345678'],
    'VAT ten digits' => [new UnitedKingdom, '1234567890'],
    'VAT eleven digits' => [new UnitedKingdom, '12345678901'],
    'VAT too long' => [new UnitedKingdom, '1234567890123'],
    'VAT wrong prefix' => [new UnitedKingdom, 'UK553557881'],
    'VAT letters' => [new UnitedKingdom, '55355788A'],
    'VAT zeros' => [new UnitedKingdom, '000000000'],
    'VAT branch zero base' => [new UnitedKingdom, '000000000001'],
    'VAT branch zeros' => [new UnitedKingdom, '000000000000'],
    'EIN too short' => [new UnitedStates, '12-345678'],
    'EIN too long' => [new UnitedStates, '12-34567890'],
    'EIN letters' => [new UnitedStates, '12-345678A'],
    'EIN zeros' => [new UnitedStates, '00-0000000'],
    'INN too short' => [new Russia, '770708389'],
    'INN eleven digits' => [new Russia, '77070838930'],
    'INN company checksum' => [new Russia, '7707083894'],
    'INN personal first checksum' => [new Russia, '784806113673'],
    'INN personal second checksum' => [new Russia, '784806113664'],
    'INN company zeros' => [new Russia, '0000000000'],
    'INN personal zeros' => [new Russia, '000000000000'],
    'INN letters' => [new Russia, '7707O83893'],
    'INN internal space' => [new Russia, '7707 083893'],
]);

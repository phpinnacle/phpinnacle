<?php

use PHPinnacle\Common\TaxNumber\Countries\Belarus;

it('accepts organization and personal UNPs with valid checksums', function (string $number) {
    expect(new Belarus()->validate($number))->toBeTrue();
})->with(['100000007', 'AA1234562', 'MA1953684']);

it('rejects invalid UNP structures and checksums', function (string $number) {
    expect(new Belarus()->validate($number))->toBeFalse();
})->with([
    'empty' => '',
    'short' => '10000007',
    'long' => '1000000007',
    'wrong checksum' => '100000008',
    'remainder ten' => '100000010',
    'invalid region' => '000000000',
    'one initial letter' => 'A00000004',
    'invalid first letter' => 'ZA1234560',
    'invalid second letter' => 'AZ1234560',
    'internal letter' => '100A00007',
    'trailing letter' => '10000000A',
    'trailing newline' => "100000007\n",
]);

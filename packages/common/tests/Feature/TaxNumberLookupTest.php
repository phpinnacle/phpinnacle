<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use PHPinnacle\Common\TaxNumber\Countries\Belarus;
use PHPinnacle\Common\TaxNumber\Country;
use PHPinnacle\Common\TaxNumber\Lookup;
use PHPinnacle\Common\TaxNumber\LookupFailure;
use PHPinnacle\Common\TaxNumber\LookupResult;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Http::preventStrayRequests();
});

it('rejects invalid numbers and handles personal UNPs without requesting information', function () {
    expect(new Lookup()->resolve(new Belarus, 'invalid'))
        ->toEqual(LookupResult::failure(LookupFailure::InvalidNumber));
    expect(new Lookup()->resolve(new Belarus, 'AA1234562'))
        ->toEqual(LookupResult::failure(LookupFailure::NotFound));
    Http::assertNothingSent();
});

it('reads organization and entrepreneur names and statuses', function (array $record, string $name, ?string $status) {
    Http::fake(['egr.gov.by/api/v2/egr/getShortInfoByRegNum/100000007' => Http::response([$record])]);

    $result = new Lookup()->resolve(new Belarus, ' 100000007 ');

    expect($result)->toEqual(LookupResult::success($name, $status));
    Http::assertSent(
        fn (Request $request) => $request->url() === 'https://egr.gov.by/api/v2/egr/getShortInfoByRegNum/100000007',
    );
})->with([
    'short name' => [
        ['vn' => 'Short name', 'vnaim' => 'Full name', 'nsi00219' => ['vnsostk' => 'Active']],
        'Short name',
        'Active',
    ],
    'full name' => [['vnaim' => 'Full name'], 'Full name', null],
    'entrepreneur' => [['vfio' => 'Jane Smith'], 'Jane Smith', null],
]);

it('distinguishes absent records from unavailable or malformed responses', function (
    mixed $payload,
    int $status,
    LookupFailure $expected,
) {
    Sleep::fake();
    Http::fake(['egr.gov.by/api/v2/egr/getShortInfoByRegNum/100000007' => Http::response($payload, $status)]);

    expect(new Lookup()->resolve(new Belarus, '100000007'))->toEqual(LookupResult::failure($expected));
    Http::assertSentCount($status === 503 ? 2 : 1);
})->with([
    'empty list' => [[], 200, LookupFailure::NotFound],
    'no content' => ['', 204, LookupFailure::NotFound],
    'missing record' => ['', 404, LookupFailure::NotFound],
    'server failure' => ['', 503, LookupFailure::Unavailable],
    'unauthorized' => ['', 403, LookupFailure::Unavailable],
    'invalid json' => ['not json', 200, LookupFailure::Unavailable],
    'wrong root' => [['error' => 'failed'], 200, LookupFailure::Unavailable],
    'wrong record' => [[42], 200, LookupFailure::Unavailable],
    'wrong name type' => [[['vn' => 42]], 200, LookupFailure::Unavailable],
    'missing name' => [[['nsi00219' => ['vnsostk' => 'Active']]], 200, LookupFailure::Unavailable],
    'wrong status type' => [[['vn' => 'Name', 'nsi00219' => ['vnsostk' => []]]], 200, LookupFailure::Unavailable],
]);

it('returns unavailable after connection failures', function () {
    Sleep::fake();
    Http::fake(['egr.gov.by/api/v2/egr/getShortInfoByRegNum/100000007' => Http::failedConnection()]);

    expect(new Lookup()->resolve(new Belarus, '100000007'))
        ->toEqual(LookupResult::failure(LookupFailure::Unavailable));
});

it('caches successful responses in the selected store and refreshes them after expiry', function () {
    $this->freezeTime();
    config(['cache.stores.tax_numbers' => ['driver' => 'array']]);
    Http::fake(['egr.gov.by/api/v2/egr/getShortInfoByRegNum/100000007' => Http::sequence()
        ->push([['vn' => 'First']])
        ->push([['vn' => 'Updated']])]);
    $lookup = new Lookup(Cache::store('tax_numbers'), 60);

    expect($lookup->resolve(new Belarus, ' 100000007 '))
        ->toEqual(LookupResult::success('First'));
    expect($lookup->resolve(new Belarus, '100000007'))
        ->toEqual(LookupResult::success('First'));
    expect(Cache::store('array')->getStore()->all())->toBe([]);
    $this->travel(61)->seconds();
    expect($lookup->resolve(new Belarus, '100000007'))
        ->toEqual(LookupResult::success('Updated'));
    Http::assertSentCount(2);
});

it('does not cache when no store is selected or TTL is nonpositive', function (?string $store, int $ttl) {
    Http::fake(['egr.gov.by/api/v2/egr/getShortInfoByRegNum/100000007' => Http::response([['vn' => 'Name']])]);

    $lookup = new Lookup($store === null ? null : Cache::store($store), $ttl);
    $lookup->resolve(new Belarus, '100000007');
    $lookup->resolve(new Belarus, '100000007');

    Http::assertSentCount(2);
})->with([[null, 60], ['array', 0], ['array', -1]]);

it('does not cache missing records or source failures', function (array $payload, int $status) {
    Http::fake(['egr.gov.by/api/v2/egr/getShortInfoByRegNum/100000007' => Http::sequence()
        ->push($payload, $status)
        ->push([['vn' => 'Recovered']])]);

    new Lookup(Cache::store('array'))->resolve(new Belarus, '100000007');
    expect(new Lookup(Cache::store('array'))->resolve(new Belarus, '100000007'))
        ->toEqual(LookupResult::success('Recovered'));
    Http::assertSentCount(2);
})->with([[[], 200], [[], 403], [['unexpected' => true], 200]]);

it('keeps cached results separate for different sources of the same country', function () {
    $country = new class implements Country {
        public function code(): string
        {
            return 'BY';
        }

        public function normalize(string $number): string
        {
            return $number;
        }

        public function validate(string $number): bool
        {
            return $number === '100000007';
        }

        public function lookup(string $number): LookupResult
        {
            return LookupResult::success('Custom taxpayer', 'Active', 'Custom address');
        }
    };

    Http::fake(['egr.gov.by/api/v2/egr/getShortInfoByRegNum/100000007' => Http::response([['vn' => 'EGR taxpayer']])]);

    expect(new Lookup(Cache::store('array'))->resolve($country, '100000007'))
        ->toEqual(LookupResult::success('Custom taxpayer', 'Active', 'Custom address'));
    expect(new Lookup(Cache::store('array'))->resolve(new Belarus, '100000007'))
        ->toEqual(LookupResult::success('EGR taxpayer'));
    Http::assertSentCount(1);
});

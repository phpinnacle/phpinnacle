<?php

use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPinnacle\Common\TaxNumber\Countries\Poland;
use PHPinnacle\Common\TaxNumber\Countries\Russia;
use PHPinnacle\Common\TaxNumber\Countries\UnitedKingdom;
use PHPinnacle\Common\TaxNumber\Countries\UnitedStates;
use PHPinnacle\Common\TaxNumber\Lookup;
use PHPinnacle\Common\TaxNumber\LookupFailure;
use PHPinnacle\Common\TaxNumber\LookupResult;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Http::preventStrayRequests();
});

it('validates Polish numbers without network calls', function () {
    $source = new Poland;

    expect($source->validate($source->normalize('5260250274')))->toBeTrue();
    Http::assertNothingSent();
});

it('returns unavailable without a configured lookup source and still validates numbers', function (
    string $country,
    string $number,
) {
    $source = new $country;

    expect($source->validate($source->normalize($number)))->toBeTrue();
    expect(new Lookup()->resolve($source, $number))
        ->toEqual(LookupResult::failure(LookupFailure::Unavailable));
    Http::assertNothingSent();
})->with([
    'GB' => [UnitedKingdom::class, '553557881'],
    'US' => [UnitedStates::class, '123456789'],
    'RU' => [Russia::class, '7707083893'],
]);

it('looks up Polish taxpayers on the Warsaw date and shares cache entries across NIP presentations', function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-03 23:30:00', 'UTC'));
    Http::fake(['wykazpodatnikowvat.mf.gov.pl/api/search/nip/5260250274*' => Http::response([
        'result' => ['subject' => [
            'name' => 'Example company',
            'statusVat' => 'Czynny',
            'workingAddress' => 'Warsaw, Office 1',
            'residenceAddress' => 'Warsaw, Residence 2',
        ]],
    ])]);

    expect(new Lookup(Cache::store('array'))->resolve(new Poland, 'PL526-025-02-74'))
        ->toEqual(LookupResult::success('Example company', 'Czynny', 'Warsaw, Office 1'));
    expect(new Lookup(Cache::store('array'))->resolve(new Poland, '5260250274'))
        ->toEqual(LookupResult::success('Example company', 'Czynny', 'Warsaw, Office 1'));
    Http::assertSent(
        fn (Request $request) => (
            $request->url() === 'https://wykazpodatnikowvat.mf.gov.pl/api/search/nip/5260250274?date=2026-11-04'
        ),
    );
    Http::assertSentCount(1);
});

it('uses the Polish residence address when no working address is provided', function () {
    Http::fake(['wykazpodatnikowvat.mf.gov.pl/api/search/nip/5260250274*' => Http::response([
        'result' => ['subject' => [
            'name' => 'Example',
            'statusVat' => 'Zwolniony',
            'workingAddress' => null,
            'residenceAddress' => 'Residence',
        ]],
    ])]);

    expect(new Lookup()->resolve(new Poland, '5260250274'))
        ->toEqual(LookupResult::success('Example', 'Zwolniony', 'Residence'));
    Http::assertSentCount(1);
});

it('uses authenticated HMRC v2 and preserves the complete VAT number', function (string $number) {
    Http::fake([
        'api.service.hmrc.gov.uk/organisations/vat/check-vat-number/lookup/' . $number => Http::response([
            'target' => [
                'name' => 'UK company',
                'address' => ['line1' => '1 Example Street', 'postcode' => 'SW1A 1AA', 'countryCode' => 'GB'],
            ],
        ]),
    ]);

    expect(new Lookup()->resolve(new UnitedKingdom('hmrc-test-token'), 'GB' . $number))
        ->toEqual(LookupResult::success('UK company', 'VAT registered', '1 Example Street, SW1A 1AA, GB'));
    Http::assertSent(
        fn (Request $request) => (
            $request->url() === 'https://api.service.hmrc.gov.uk/organisations/vat/check-vat-number/lookup/' . $number
            && $request->hasHeader('Authorization', 'Bearer hmrc-test-token')
            && $request->hasHeader('Accept', 'application/vnd.hmrc.2.0+json')
        ),
    );
})->with(['company' => '553557881', 'branch' => '146295999727']);

it('looks up Russian companies and entrepreneurs through DaData without returning branches', function (string $number) {
    Http::fake(['suggestions.dadata.ru/suggestions/api/4_1/rs/findById/party' => Http::response([
        'suggestions' => [[
            'value' => 'Example taxpayer',
            'data' => ['state' => ['status' => 'ACTIVE'], 'address' => ['value' => 'Example address']],
        ]],
    ])]);

    expect(new Lookup()->resolve(new Russia('dadata-test-token'), $number))
        ->toEqual(LookupResult::success('Example taxpayer', 'ACTIVE', 'Example address'));
    Http::assertSent(
        fn (Request $request) => (
            $request->method() === 'POST'
            && $request->hasHeader('Authorization', 'Token dadata-test-token')
            && $request->data() === ['query' => $number, 'branch_type' => 'MAIN']
        ),
    );
})->with(['company' => '7707083893', 'entrepreneur' => '784806113663']);

it('distinguishes missing registrations from source errors and malformed responses', function (
    string $country,
    mixed $payload,
    int $status,
    LookupFailure $expected,
) {
    $source = match ($country) {
        Poland::class => new Poland,
        UnitedKingdom::class => new UnitedKingdom('hmrc-test-token'),
        Russia::class => new Russia('dadata-test-token'),
    };
    [$number, $url] = match ($country) {
        Poland::class => ['5260250274', 'wykazpodatnikowvat.mf.gov.pl/api/search/nip/5260250274*'],
        UnitedKingdom::class => [
            '553557881',
            'api.service.hmrc.gov.uk/organisations/vat/check-vat-number/lookup/553557881',
        ],
        Russia::class => ['7707083893', 'suggestions.dadata.ru/suggestions/api/4_1/rs/findById/party'],
    };
    Http::fake([$url => Http::response($payload, $status)]);

    expect(new Lookup()->resolve($source, $number))->toEqual(LookupResult::failure($expected));
    Http::assertSentCount(1);
})->with([
    'PL absent' => [Poland::class, ['result' => ['subject' => null]], 200, LookupFailure::NotFound],
    'PL failure' => [Poland::class, [], 503, LookupFailure::Unavailable],
    'PL unexpected 404' => [Poland::class, [], 404, LookupFailure::Unavailable],
    'PL bad JSON' => [Poland::class, 'invalid', 200, LookupFailure::Unavailable],
    'PL missing result' => [Poland::class, [], 200, LookupFailure::Unavailable],
    'PL missing subject' => [Poland::class, ['result' => ['requestId' => 'test']], 200, LookupFailure::Unavailable],
    'PL wrong name type' => [
        Poland::class,
        ['result' => ['subject' => ['name' => []]]],
        200,
        LookupFailure::Unavailable,
    ],
    'PL empty subject' => [Poland::class, ['result' => ['subject' => []]], 200, LookupFailure::Unavailable],
    'PL missing name' => [
        Poland::class,
        ['result' => ['subject' => ['statusVat' => 'Czynny']]],
        200,
        LookupFailure::Unavailable,
    ],
    'PL wrong address type' => [
        Poland::class,
        ['result' => ['subject' => ['name' => 'Test', 'workingAddress' => []]]],
        200,
        LookupFailure::Unavailable,
    ],
    'GB absent' => [UnitedKingdom::class, ['code' => 'NOT_FOUND'], 404, LookupFailure::NotFound],
    'GB credentials rejected' => [UnitedKingdom::class, [], 401, LookupFailure::Unavailable],
    'GB unavailable' => [UnitedKingdom::class, [], 503, LookupFailure::Unavailable],
    'GB missing target' => [UnitedKingdom::class, [], 200, LookupFailure::Unavailable],
    'GB malformed address' => [
        UnitedKingdom::class,
        ['target' => ['name' => 'Test', 'address' => []]],
        200,
        LookupFailure::Unavailable,
    ],
    'RU absent' => [Russia::class, ['suggestions' => []], 200, LookupFailure::NotFound],
    'RU credentials rejected' => [Russia::class, [], 403, LookupFailure::Unavailable],
    'RU unavailable' => [Russia::class, [], 503, LookupFailure::Unavailable],
    'RU missing suggestions' => [Russia::class, [], 200, LookupFailure::Unavailable],
    'RU malformed suggestions' => [Russia::class, ['suggestions' => 'unexpected'], 200, LookupFailure::Unavailable],
    'RU empty record' => [Russia::class, ['suggestions' => [[]]], 200, LookupFailure::Unavailable],
    'RU wrong name type' => [Russia::class, ['suggestions' => [['value' => []]]], 200, LookupFailure::Unavailable],
    'RU wrong status type' => [
        Russia::class,
        ['suggestions' => [['value' => 'Test', 'data' => ['state' => ['status' => []]]]]],
        200,
        LookupFailure::Unavailable,
    ],
]);

it('recovers from connection failures for each remote source', function (string $country, string $number, string $url) {
    $source = match ($country) {
        Poland::class => new Poland,
        UnitedKingdom::class => new UnitedKingdom('hmrc-test-token'),
        Russia::class => new Russia('dadata-test-token'),
    };
    Http::fake([$url => Http::failedConnection()]);

    expect(new Lookup()->resolve($source, $number))
        ->toEqual(LookupResult::failure(LookupFailure::Unavailable));
})->with([
    'PL' => [Poland::class, '5260250274', 'wykazpodatnikowvat.mf.gov.pl/api/search/nip/5260250274*'],
    'GB' => [
        UnitedKingdom::class,
        '553557881',
        'api.service.hmrc.gov.uk/organisations/vat/check-vat-number/lookup/553557881',
    ],
    'RU' => [Russia::class, '7707083893', 'suggestions.dadata.ru/suggestions/api/4_1/rs/findById/party'],
]);

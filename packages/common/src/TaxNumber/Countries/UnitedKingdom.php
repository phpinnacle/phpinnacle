<?php

namespace PHPinnacle\Common\TaxNumber\Countries;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use PHPinnacle\Common\TaxNumber\Country;
use PHPinnacle\Common\TaxNumber\LookupFailure;
use PHPinnacle\Common\TaxNumber\LookupResult;
use SensitiveParameter;

final class UnitedKingdom implements Country
{
    public function __construct(
        #[SensitiveParameter]
        private readonly ?string $token = null,
    ) {}

    public function code(): string
    {
        return 'GB';
    }

    public function normalize(string $number): string
    {
        $number = str_replace(' ', '', strtoupper(trim($number)));

        return str_starts_with($number, 'GB') ? substr($number, 2) : $number;
    }

    public function validate(string $number): bool
    {
        return (bool) preg_match('/\A(?!0{9})[0-9]{9}(?:[0-9]{3})?\z/', $number);
    }

    public function lookup(string $number): LookupResult
    {
        if (blank($this->token)) {
            return LookupResult::failure(LookupFailure::Unavailable);
        }

        try {
            $response = Http::timeout(5)
                ->connectTimeout(3)
                ->withToken($this->token)
                ->accept('application/vnd.hmrc.2.0+json')
                ->get('https://api.service.hmrc.gov.uk/organisations/vat/check-vat-number/lookup/' . $number);
        } catch (ConnectionException $exception) {
            report($exception);

            return LookupResult::failure(LookupFailure::Unavailable);
        }

        if ($response->notFound()) {
            return LookupResult::failure(LookupFailure::NotFound);
        }

        if (!$response->successful()) {
            return LookupResult::failure(LookupFailure::Unavailable);
        }

        $validator = Validator::make(['payload' => $response->json()], [
            'payload' => ['required', 'array'],
            'payload.target' => ['required', 'array'],
            'payload.target.name' => ['required', 'string'],
            'payload.target.address' => ['required', 'array'],
            'payload.target.address.line1' => ['required', 'string'],
            'payload.target.address.postcode' => ['required', 'string'],
            'payload.target.address.countryCode' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return LookupResult::failure(LookupFailure::Unavailable);
        }

        /** @var array{name: string, address: array{line1: string, postcode: string, countryCode: string}} $target */
        $target = $validator->validated()['payload']['target'];
        $address = $target['address'];

        return LookupResult::success(
            $target['name'],
            'VAT registered',
            implode(', ', [
                $address['line1'],
                $address['postcode'],
                $address['countryCode'],
            ]),
        );
    }
}

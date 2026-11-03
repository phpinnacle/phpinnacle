<?php

namespace PHPinnacle\Common\TaxNumber\Countries;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use PHPinnacle\Common\TaxNumber\Country;
use PHPinnacle\Common\TaxNumber\LookupFailure;
use PHPinnacle\Common\TaxNumber\LookupResult;
use SensitiveParameter;

final class Russia implements Country
{
    public function __construct(
        #[SensitiveParameter]
        private readonly ?string $token = null,
    ) {}

    public function code(): string
    {
        return 'RU';
    }

    public function normalize(string $number): string
    {
        return trim($number);
    }

    public function validate(string $number): bool
    {
        if (!preg_match('/\A(?!0+\z)(?:[0-9]{10}|[0-9]{12})\z/', $number)) {
            return false;
        }

        if (strlen($number) === 10) {
            return $this->checkDigit($number, [2, 4, 10, 3, 5, 9, 4, 6, 8]) === (int) $number[9];
        }

        return (
            $this->checkDigit($number, [7, 2, 4, 10, 3, 5, 9, 4, 6, 8]) === (int) $number[10]
            && $this->checkDigit($number, [3, 7, 2, 4, 10, 3, 5, 9, 4, 6, 8]) === (int) $number[11]
        );
    }

    public function lookup(string $number): LookupResult
    {
        if (blank($this->token)) {
            return LookupResult::failure(LookupFailure::Unavailable);
        }

        try {
            $response = Http::timeout(5)
                ->connectTimeout(3)
                ->acceptJson()
                ->withToken($this->token, 'Token')
                ->post('https://suggestions.dadata.ru/suggestions/api/4_1/rs/findById/party', [
                    'query' => $number,
                    'branch_type' => 'MAIN',
                ]);
        } catch (ConnectionException $exception) {
            report($exception);

            return LookupResult::failure(LookupFailure::Unavailable);
        }

        if (!$response->successful()) {
            return LookupResult::failure(LookupFailure::Unavailable);
        }

        $validator = Validator::make(['payload' => $response->json()], [
            'payload' => ['required', 'array'],
            'payload.suggestions' => ['present', 'array', 'list'],
            'payload.suggestions.*' => ['required', 'array'],
            'payload.suggestions.*.value' => ['required', 'string'],
            'payload.suggestions.*.data' => ['nullable', 'array'],
            'payload.suggestions.*.data.state' => ['nullable', 'array'],
            'payload.suggestions.*.data.address' => ['nullable', 'array'],
            'payload.suggestions.*.data.state.status' => ['nullable', 'string'],
            'payload.suggestions.*.data.address.value' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return LookupResult::failure(LookupFailure::Unavailable);
        }

        /** @var list<array{value: string, data?: array{state?: array{status?: ?string}, address?: array{value?: ?string}}}> $suggestions */
        $suggestions = $validator->validated()['payload']['suggestions'];

        if ($suggestions === []) {
            return LookupResult::failure(LookupFailure::NotFound);
        }

        $suggestion = $suggestions[0];

        return LookupResult::success(
            $suggestion['value'],
            $suggestion['data']['state']['status'] ?? null,
            $suggestion['data']['address']['value'] ?? null,
        );
    }

    /** @param list<int> $weights */
    private function checkDigit(string $number, array $weights): int
    {
        $sum = 0;

        foreach ($weights as $index => $weight) {
            $sum += (int) $number[$index] * $weight;
        }

        return $sum % 11 % 10;
    }
}

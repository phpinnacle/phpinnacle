<?php

namespace PHPinnacle\Common\TaxNumber\Countries;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use PHPinnacle\Common\TaxNumber\Country;
use PHPinnacle\Common\TaxNumber\LookupFailure;
use PHPinnacle\Common\TaxNumber\LookupResult;

final class Poland implements Country
{
    public function code(): string
    {
        return 'PL';
    }

    public function normalize(string $number): string
    {
        $number = str_replace([' ', '-'], '', strtoupper(trim($number)));

        return str_starts_with($number, 'PL') ? substr($number, 2) : $number;
    }

    public function validate(string $number): bool
    {
        if (!preg_match('/\A(?!0{10}\z)[0-9]{10}\z/', $number)) {
            return false;
        }

        $sum = 0;

        foreach ([6, 5, 7, 2, 3, 4, 5, 6, 7] as $index => $weight) {
            $sum += (int) $number[$index] * $weight;
        }

        return ($sum % 11) === (int) $number[9];
    }

    public function lookup(string $number): LookupResult
    {
        try {
            $response = Http::timeout(5)
                ->connectTimeout(3)
                ->acceptJson()
                ->get('https://wykazpodatnikowvat.mf.gov.pl/api/search/nip/' . $number, [
                    'date' => now('Europe/Warsaw')->toDateString(),
                ]);
        } catch (ConnectionException $exception) {
            report($exception);

            return LookupResult::failure(LookupFailure::Unavailable);
        }

        if (!$response->successful()) {
            return LookupResult::failure(LookupFailure::Unavailable);
        }

        $payload = $response->json();
        $validator = Validator::make(['payload' => $payload], [
            'payload' => ['required', 'array'],
            'payload.result' => ['required', 'array'],
            'payload.result.subject' => ['present', 'nullable', 'array'],
            'payload.result.subject.name' => [
                Rule::requiredIf(data_get($payload, 'result.subject') !== null),
                'string',
            ],
            'payload.result.subject.statusVat' => ['nullable', 'string'],
            'payload.result.subject.workingAddress' => ['nullable', 'string'],
            'payload.result.subject.residenceAddress' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return LookupResult::failure(LookupFailure::Unavailable);
        }

        /** @var array{name: string, statusVat?: ?string, workingAddress?: ?string, residenceAddress?: ?string}|null $subject */
        $subject = $validator->validated()['payload']['result']['subject'];

        if ($subject === null) {
            return LookupResult::failure(LookupFailure::NotFound);
        }

        return LookupResult::success(
            $subject['name'],
            $subject['statusVat'] ?? null,
            $subject['workingAddress'] ?? $subject['residenceAddress'] ?? null,
        );
    }
}

<?php

namespace PHPinnacle\Common\TaxNumber\Countries;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use PHPinnacle\Common\TaxNumber\Country;
use PHPinnacle\Common\TaxNumber\LookupFailure;
use PHPinnacle\Common\TaxNumber\LookupResult;
use Throwable;

final class Belarus implements Country
{
    private const WEIGHTS = [29, 23, 19, 17, 13, 7, 5, 3];

    public function code(): string
    {
        return 'BY';
    }

    public function normalize(string $number): string
    {
        return strtoupper(trim($number));
    }

    public function validate(string $number): bool
    {
        if (!preg_match('/\A(?:[1-9][0-9]{8}|[ABCEHKM][ABCEHKMOPT][0-9]{7})\z/', $number)) {
            return false;
        }

        $sum = 0;

        for ($index = 0; $index < 8; $index++) {
            $digit = (int) $number[$index];

            if ($index === 0 && ctype_alpha($number[0])) {
                $digit = ord($number[0]) - ord('A') + 10;
            }

            if ($index === 1 && ctype_alpha($number[1])) {
                $digit = strpos('ABCEHKMOPT', $number[1]);
            }

            $sum += $digit * self::WEIGHTS[$index];
        }

        return ($sum % 11) === (int) $number[8];
    }

    public function lookup(string $number): LookupResult
    {
        // EGR contains organizations and entrepreneurs, not personal taxpayer accounts.
        if (!ctype_digit($number)) {
            return LookupResult::failure(LookupFailure::NotFound);
        }

        try {
            $response = Http::timeout(5)
                ->connectTimeout(3)
                ->retry(
                    2,
                    150,
                    fn (Throwable $exception) => (
                        $exception instanceof ConnectionException
                        || $exception instanceof RequestException
                        && ($exception->response->serverError() || $exception->response->status() === 429)
                    ),
                    throw: false,
                )
                ->acceptJson()
                ->get('https://egr.gov.by/api/v2/egr/getShortInfoByRegNum/' . $number);
        } catch (ConnectionException $exception) {
            report($exception);

            return LookupResult::failure(LookupFailure::Unavailable);
        }

        if ($response->status() === 204 || $response->notFound()) {
            return LookupResult::failure(LookupFailure::NotFound);
        }

        if (!$response->successful()) {
            Log::warning('EGR lookup failed.', ['status' => $response->status()]);

            return LookupResult::failure(LookupFailure::Unavailable);
        }

        $payload = $response->json();

        if (!is_array($payload) || !array_is_list($payload)) {
            return LookupResult::failure(LookupFailure::Unavailable);
        }

        if ($payload === []) {
            return LookupResult::failure(LookupFailure::NotFound);
        }

        $validator = Validator::make(['record' => $payload[0]], [
            'record' => ['required', 'array'],
            'record.vn' => ['nullable', 'string'],
            'record.vnaim' => ['nullable', 'string'],
            'record.vfio' => ['nullable', 'string'],
            'record.nsi00219' => ['nullable', 'array'],
            'record.nsi00219.vnsostk' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return LookupResult::failure(LookupFailure::Unavailable);
        }

        /** @var array{vn?: ?string, vnaim?: ?string, vfio?: ?string, nsi00219?: array{vnsostk?: ?string}} $record */
        $record = $validator->validated()['record'];
        $name = $record['vn'] ?? $record['vnaim'] ?? $record['vfio'] ?? null;

        if ($name === null) {
            return LookupResult::failure(LookupFailure::Unavailable);
        }

        return LookupResult::success($name, $record['nsi00219']['vnsostk'] ?? null);
    }
}

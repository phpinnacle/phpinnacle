<?php

namespace PHPinnacle\Canvio\Exception;

use RuntimeException;

final class ApiException extends RuntimeException
{
    /** @param array<array-key, mixed>|null $response */
    public function __construct(
        public readonly int $statusCode,
        public readonly string $responseBody,
        public readonly ?array $response = null,
    ) {
        parent::__construct(self::message($response) ?? "Canvas API returned HTTP {$statusCode}.", $statusCode);
    }

    /** @param array<array-key, mixed>|null $response */
    private static function message(?array $response): ?string
    {
        if (is_string($response['message'] ?? null)) {
            return $response['message'];
        }

        $errors = $response['errors'] ?? null;

        if (!is_array($errors)) {
            return null;
        }

        foreach ($errors as $error) {
            if (is_array($error) && is_string($error['message'] ?? null)) {
                return $error['message'];
            }

            if (is_array($error)) {
                foreach ($error as $detail) {
                    if (is_array($detail) && is_string($detail['message'] ?? null)) {
                        return $detail['message'];
                    }
                }
            }
        }

        return null;
    }
}

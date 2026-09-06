<?php

namespace PHPinnacle\Franz\Exception;

use RuntimeException;

final class ApiException extends RuntimeException
{
    public readonly ?int $errorCode;

    /** @param array<array-key, mixed>|null $response */
    public function __construct(
        public readonly int $statusCode,
        public readonly string $responseBody,
        public readonly ?array $response = null,
    ) {
        $this->errorCode = is_int($response['error_code'] ?? null) ? $response['error_code'] : null;

        parent::__construct(
            is_string($response['message'] ?? null)
                ? $response['message']
                : "Kafka Connect API returned HTTP {$statusCode}.",
            $statusCode,
        );
    }
}

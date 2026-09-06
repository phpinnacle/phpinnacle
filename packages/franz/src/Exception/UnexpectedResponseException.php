<?php

namespace PHPinnacle\Franz\Exception;

use RuntimeException;
use Throwable;

final class UnexpectedResponseException extends RuntimeException
{
    public function __construct(
        public readonly int $statusCode,
        public readonly string $responseBody,
        string $message = 'Kafka Connect API returned an invalid JSON response.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $previous);
    }
}

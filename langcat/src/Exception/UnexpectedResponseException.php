<?php

namespace PHPinnacle\Langcat\Exception;

use JsonException;
use RuntimeException;

final class UnexpectedResponseException extends RuntimeException
{
    public function __construct(
        public readonly int $statusCode,
        public readonly string $responseBody,
        ?JsonException $previous = null,
    ) {
        parent::__construct('LangLion API returned an invalid JSON response.', $statusCode, $previous);
    }
}

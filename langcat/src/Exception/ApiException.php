<?php

namespace PHPinnacle\Langcat\Exception;

use PHPinnacle\Langcat\Response\Shared\ErrorResponse;
use RuntimeException;

final class ApiException extends RuntimeException
{
    public function __construct(
        public readonly int $statusCode,
        public readonly string $responseBody,
        public readonly ?ErrorResponse $response = null,
    ) {
        $message = $response === null ? null : $response->message;

        parent::__construct($message ?? "LangLion API returned HTTP {$statusCode}.", $statusCode);
    }
}

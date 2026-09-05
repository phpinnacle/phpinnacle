<?php

namespace PHPinnacle\Langcat\Response\Shared;

use UnexpectedValueException;

final readonly class EmptyResponse
{
    /** @param array<array-key, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        if ($payload !== []) {
            throw new UnexpectedValueException('LangLion API response must be empty.');
        }

        return new self;
    }
}

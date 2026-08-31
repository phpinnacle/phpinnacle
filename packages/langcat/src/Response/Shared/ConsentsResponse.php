<?php

namespace PHPinnacle\Langcat\Response\Shared;

use PHPinnacle\Langcat\Support\ResponseValue;
use UnexpectedValueException;

final readonly class ConsentsResponse
{
    /** @param list<ConsentResponse> $data */
    public function __construct(
        public array $data,
        public PaginationResponse $meta,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            array_map(
                static fn (mixed $consent) => ConsentResponse::fromArray(
                    is_array($consent)
                        ? $consent
                        : throw new UnexpectedValueException('Each consent must be an object.'),
                ),
                ResponseValue::list($payload, 'data'),
            ),
            PaginationResponse::fromArray(ResponseValue::object($payload, 'meta')),
        );
    }
}

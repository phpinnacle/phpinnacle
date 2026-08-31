<?php

namespace PHPinnacle\Langcat\Response\Administrators;

use PHPinnacle\Langcat\Response\Shared\PaginationResponse;
use PHPinnacle\Langcat\Support\ResponseValue;
use UnexpectedValueException;

final readonly class AdministratorsResponse
{
    /** @param list<AdministratorResponse> $data */
    public function __construct(
        public array $data,
        public PaginationResponse $meta,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            array_map(
                static fn (mixed $item) => AdministratorResponse::fromArray(
                    is_array($item)
                        ? $item
                        : throw new UnexpectedValueException('Each administrator must be an object.'),
                ),
                ResponseValue::list($payload, 'data'),
            ),
            PaginationResponse::fromArray(ResponseValue::object($payload, 'meta')),
        );
    }
}

<?php

namespace PHPinnacle\Langcat\Response\Finances;

use PHPinnacle\Langcat\Response\Shared\PaginationResponse;
use PHPinnacle\Langcat\Support\ResponseValue;

final readonly class InstallmentCollectionsResponse
{
    /** @param list<InstallmentCollectionResponse> $data */
    public function __construct(
        public array $data,
        public PaginationResponse $meta,
    ) {}

    /** @param array<array-key, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            ResponseValue::objects($payload, 'data', InstallmentCollectionResponse::fromArray(...)),
            PaginationResponse::fromArray(ResponseValue::object($payload, 'meta')),
        );
    }
}

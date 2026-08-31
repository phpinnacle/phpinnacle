<?php

namespace PHPinnacle\Langcat\Response\GroupSettings;

use PHPinnacle\Langcat\Response\Shared\PaginationResponse;
use PHPinnacle\Langcat\Support\ResponseValue;

final readonly class GradeCollectionsResponse
{
    /** @param list<GradeCollectionResponse> $data */
    public function __construct(
        public array $data,
        public PaginationResponse $meta,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            ResponseValue::objects($payload, 'data', GradeCollectionResponse::fromArray(...)),
            PaginationResponse::fromArray(ResponseValue::object($payload, 'meta')),
        );
    }
}

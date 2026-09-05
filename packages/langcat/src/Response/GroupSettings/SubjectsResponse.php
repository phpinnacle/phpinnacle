<?php

namespace PHPinnacle\Langcat\Response\GroupSettings;

use PHPinnacle\Langcat\Response\Shared\PaginationResponse;
use PHPinnacle\Langcat\Support\ResponseValue;

final readonly class SubjectsResponse
{
    /** @param list<SubjectResponse> $data */
    public function __construct(
        public array $data,
        public PaginationResponse $meta,
    ) {}

    /** @param array<array-key, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            ResponseValue::objects($payload, 'data', SubjectResponse::fromArray(...)),
            PaginationResponse::fromArray(ResponseValue::object($payload, 'meta')),
        );
    }
}

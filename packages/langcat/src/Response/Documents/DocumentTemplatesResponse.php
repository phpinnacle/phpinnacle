<?php

namespace PHPinnacle\Langcat\Response\Documents;

use PHPinnacle\Langcat\Response\Shared\PaginationResponse;
use PHPinnacle\Langcat\Support\ResponseValue;
use UnexpectedValueException;

final readonly class DocumentTemplatesResponse
{
    /** @param list<DocumentTemplateResponse> $data */
    public function __construct(
        public array $data,
        public PaginationResponse $meta,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            array_map(
                static fn (mixed $item) => DocumentTemplateResponse::fromArray(
                    is_array($item)
                        ? $item
                        : throw new UnexpectedValueException('Each document template must be an object.'),
                ),
                ResponseValue::list($payload, 'data'),
            ),
            PaginationResponse::fromArray(ResponseValue::object($payload, 'meta')),
        );
    }
}

<?php

namespace PHPinnacle\Langcat\Response\Documents;

use PHPinnacle\Langcat\Enum\DocumentTemplateType;
use PHPinnacle\Langcat\Support\ResponseValue;

final readonly class DocumentTemplateResponse
{
    public function __construct(
        public int $id,
        public DocumentTemplateType $type,
        public string $title,
        public DocumentTemplateMarginResponse $margin,
        public string $description,
        public string $content,
        public bool $eDocument,
        public int $schoolId,
        public bool $isShared,
        public string $createdAt,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            ResponseValue::int($payload, 'id'),
            DocumentTemplateType::from(ResponseValue::string($payload, 'type')),
            ResponseValue::string($payload, 'title'),
            DocumentTemplateMarginResponse::fromArray(ResponseValue::object($payload, 'margin')),
            ResponseValue::string($payload, 'description'),
            ResponseValue::string($payload, 'content'),
            ResponseValue::bool($payload, 'eDocument'),
            ResponseValue::int($payload, 'schoolId'),
            ResponseValue::bool($payload, 'isShared'),
            ResponseValue::string($payload, 'createdAt'),
        );
    }
}

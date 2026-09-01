<?php

namespace PHPinnacle\Canvio\Response;

use PHPinnacle\Canvio\Support\ResponseValue;

final readonly class AccountResponse
{
    /** @param array<string, mixed> $raw */
    public function __construct(
        public int $id,
        public string $name,
        public ?int $parentAccountId,
        public ?int $rootAccountId,
        public ?string $workflowState,
        public ?string $sisAccountId,
        public ?string $uuid,
        public array $raw,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            ResponseValue::int($payload, 'id'),
            ResponseValue::string($payload, 'name'),
            ResponseValue::nullableInt($payload, 'parent_account_id'),
            ResponseValue::nullableInt($payload, 'root_account_id'),
            ResponseValue::nullableString($payload, 'workflow_state'),
            ResponseValue::nullableString($payload, 'sis_account_id'),
            ResponseValue::nullableString($payload, 'uuid'),
            $payload,
        );
    }
}

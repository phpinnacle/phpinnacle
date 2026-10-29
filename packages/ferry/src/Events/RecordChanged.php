<?php

namespace PHPinnacle\Ferry\Events;

use Illuminate\Database\Eloquent\Model;
use PHPinnacle\Ferry\Data\ChangeMessage;
use PHPinnacle\Ferry\Enums\ChangeOperation;

/**
 * @phpstan-import-type ChangeMetadata from ChangeMessage
 */
abstract readonly class RecordChanged
{
    /**
     * @param class-string<Model> $model
     * @param array<string, mixed>|null $before
     * @param array<string, mixed>|null $after
     * @param ChangeMetadata $metadata
     */
    public function __construct(
        public string $eventId,
        public string $model,
        public string|int $key,
        public ?array $before,
        public ?array $after,
        public array $metadata,
    ) {}

    abstract public function operation(): ChangeOperation;

    /** @return array<string, mixed> */
    public function attributes(): array
    {
        /** @var array<string, mixed> */
        return $this->after ?? $this->before;
    }
}

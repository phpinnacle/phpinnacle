<?php

namespace PHPinnacle\Franz\Request;

use PHPinnacle\Franz\Value\ConnectorOffset;

final readonly class AlterConnectorOffsetsRequest
{
    /** @param list<ConnectorOffset> $offsets */
    public function __construct(
        public array $offsets,
    ) {}

    /** @return array{offsets: list<array{partition: object, offset: object|null}>} */
    public function toArray(): array
    {
        return ['offsets' => array_map(static fn (ConnectorOffset $offset) => $offset->toArray(), $this->offsets)];
    }
}

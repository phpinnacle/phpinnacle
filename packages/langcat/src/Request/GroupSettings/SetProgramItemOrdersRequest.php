<?php

namespace PHPinnacle\Langcat\Request\GroupSettings;

use LogicException;
use PHPinnacle\Langcat\Support\RequestValue;

final class SetProgramItemOrdersRequest
{
    /** @var list<array{itemId: int, position: int}> */
    private array $items = [];

    public static function make(): self
    {
        return new self;
    }

    public function item(int $itemId, int $position): self
    {
        $this->items[] = [
            'itemId' => RequestValue::positive($itemId, 'Program item ID'),
            'position' => RequestValue::nonNegativeInt($position, 'Program item position'),
        ];

        return $this;
    }

    /** @return list<array{itemId: int, position: int}> */
    public function toArray(): array
    {
        if ($this->items === []) {
            throw new LogicException('At least one program item order is required.');
        }

        return $this->items;
    }
}

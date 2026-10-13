<?php

namespace PHPinnacle\Common\Forms\ItemPicker;

use PHPinnacle\Common\Forms\Item;
use PHPinnacle\Common\Forms\ItemGroup;

/**
 * @internal
 * @phpstan-type PickerRow array{key: string, node: Item|ItemGroup, depth: int, ancestors: list<string>, parent: ?string}
 */
final class ItemTree
{
    /** @param list<Item|ItemGroup> $nodes */
    public function __construct(
        private readonly array $nodes,
    ) {}

    /** @return list<Item> */
    public function items(): array
    {
        $items = [];

        foreach ($this->walk() as $row) {
            $node = $row['node'];

            if ($node instanceof Item) {
                $items[] = $node;
            }
        }

        return $items;
    }

    /**
     * @param list<string> $ancestors
     * @return list<PickerRow>
     */
    public function rows(string $path = '', array $ancestors = []): array
    {
        return iterator_to_array($this->walk($path, $ancestors), preserve_keys: false);
    }

    /**
     * @param list<string> $ancestors
     * @return iterable<PickerRow>
     */
    private function walk(string $path = '', array $ancestors = []): iterable
    {
        foreach ($this->nodes as $index => $node) {
            $key = $path . '-' . $index;
            yield [
                'key' => $key,
                'node' => $node,
                'depth' => count($ancestors),
                'ancestors' => $ancestors,
                'parent' => $ancestors[array_key_last($ancestors)] ?? null,
            ];

            yield from new self($node->children)->walk(
                $key,
                $node instanceof Item ? [...$ancestors, $key] : $ancestors,
            );
        }
    }
}

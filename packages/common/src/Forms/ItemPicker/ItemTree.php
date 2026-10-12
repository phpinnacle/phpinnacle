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

        foreach ($this->nodes as $node) {
            if ($node instanceof Item) {
                $items[] = $node;
            }

            array_push($items, ...new self($node->children)->items());
        }

        return $items;
    }

    /**
     * @param list<string> $ancestors
     * @return list<PickerRow>
     */
    public function rows(string $path = '', array $ancestors = []): array
    {
        $rows = [];

        foreach ($this->nodes as $index => $node) {
            $key = $path . '-' . $index;
            $rows[] = [
                'key' => $key,
                'node' => $node,
                'depth' => count($ancestors),
                'ancestors' => $ancestors,
                'parent' => $ancestors[array_key_last($ancestors)] ?? null,
            ];

            array_push($rows, ...new self($node->children)->rows(
                $key,
                $node instanceof Item ? [...$ancestors, $key] : $ancestors,
            ));
        }

        return $rows;
    }
}

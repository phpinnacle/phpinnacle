<?php

namespace PHPinnacle\Common\Forms;

use Closure;
use Filament\Forms\Components\Concerns\HasNestedRecursiveValidationRules;
use Filament\Forms\Components\Contracts\HasNestedRecursiveValidationRules as HasNestedRecursiveValidationRulesContract;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Concerns\CanBeCompact;
use PHPinnacle\Common\Enums\ItemPickerMode;

/**
 * @phpstan-type PickerRow array{key: string, node: Item|ItemGroup, depth: int, ancestors: list<string>, parent: ?string}
 * @phpstan-type PickerNode array{key: string, value: ?string, group: bool, disabled: bool, selectable: bool, branch: bool, ancestors: list<string>, parent: ?string}
 */
class ItemPicker extends Field implements HasNestedRecursiveValidationRulesContract
{
    use CanBeCompact;
    use HasNestedRecursiveValidationRules;

    protected string $view = 'phpinnacle-common::components.item-picker';

    /** @var list<Item|ItemGroup>|Closure */
    protected array|Closure $items = [];

    protected ItemPickerMode $mode = ItemPickerMode::Inline;

    protected bool|Closure $areBranchesSelectable = true;

    protected bool|Closure $isMultiple = false;

    protected bool|Closure $isCircular = false;

    /** @param list<Item|ItemGroup>|Closure $items */
    public function items(array|Closure $items): self
    {
        $this->items = $items;

        return $this;
    }

    public function mode(ItemPickerMode $mode): self
    {
        $this->mode = $mode;

        return $this;
    }

    public function branches(bool|Closure $condition = true): self
    {
        $this->areBranchesSelectable = $condition;

        return $this;
    }

    public function multiple(bool|Closure $condition = true): self
    {
        $this->isMultiple = $condition;

        return $this;
    }

    public function circular(bool|Closure $condition = true): self
    {
        $this->isCircular = $condition;

        return $this;
    }

    public function isCircular(): bool
    {
        return (bool) $this->evaluate($this->isCircular);
    }

    public function isMultiple(): bool
    {
        return (bool) $this->evaluate($this->isMultiple);
    }

    public function hasInValidationOnMultipleValues(): bool
    {
        return $this->isMultiple();
    }

    public function canSelectBranches(): bool
    {
        return (bool) $this->evaluate($this->areBranchesSelectable);
    }

    public function isItemSelectable(Item $item): bool
    {
        return !$item->disabled && ($item->children === [] || $this->canSelectBranches());
    }

    public function getMode(): ItemPickerMode
    {
        return $this->mode;
    }

    /** @return list<Item|ItemGroup> */
    public function getItems(): array
    {
        return $this->evaluate($this->items);
    }

    /** @return list<PickerRow> */
    public function getRows(): array
    {
        return $this->rows($this->getItems());
    }

    /** @return list<string> */
    public function getSelectableValues(): array
    {
        $values = [];

        foreach ($this->getRows() as $row) {
            if ($row['node'] instanceof Item && $this->isItemSelectable($row['node'])) {
                $values[] = $row['node']->value;
            }
        }

        return $values;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->viewData(fn (ItemPicker $component) => $component->pickerViewData());
        $this->default(fn (ItemPicker $component) => $component->isMultiple() ? [] : null);
        $this->dehydrateStateUsing(fn (ItemPicker $component, array|string|null $state) => $component->isMultiple()
            ? $state ?? []
            : $state);
        $this->rule('string', fn (ItemPicker $component) => !$component->isMultiple());
        $this->rules(['array', 'list'], fn (ItemPicker $component) => $component->isMultiple());
        $this->nestedRecursiveRules(
            ['string', 'distinct:strict'],
            fn (ItemPicker $component) => $component->isMultiple(),
        );
        $this->in(fn (ItemPicker $component) => $component->getSelectableValues());
    }

    /** @return array{rows: list<PickerRow>, nodes: list<PickerNode>, id: string, select: bool, disabled: bool, multiple: bool, branchesSelectable: bool} */
    private function pickerViewData(): array
    {
        $rows = $this->getRows();
        $nodes = [];

        foreach ($rows as $row) {
            $item = $row['node'];
            $isItem = $item instanceof Item;
            $nodes[] = [
                'key' => $row['key'],
                'value' => $isItem ? $item->value : null,
                'group' => !$isItem,
                'disabled' => $isItem && $item->disabled,
                'selectable' => $isItem && $this->isItemSelectable($item),
                'branch' => $item->children !== [],
                'ancestors' => $row['ancestors'],
                'parent' => $row['parent'],
            ];
        }

        return [
            'rows' => $rows,
            'nodes' => $nodes,
            'id' => $this->getId(),
            'select' => $this->getMode() === ItemPickerMode::Select,
            'disabled' => $this->isDisabled(),
            'multiple' => $this->isMultiple(),
            'branchesSelectable' => $this->canSelectBranches(),
        ];
    }

    /**
     * @param list<Item|ItemGroup> $nodes
     * @param list<string> $ancestors
     * @return list<PickerRow>
     */
    private function rows(
        array $nodes,
        array $ancestors = [],
        string $path = '',
    ): array {
        $rows = [];

        foreach ($nodes as $index => $node) {
            $key = $path . '-' . $index;
            $rows[] = [
                'key' => $key,
                'node' => $node,
                'depth' => count($ancestors),
                'ancestors' => $ancestors,
                'parent' => $ancestors[array_key_last($ancestors)] ?? null,
            ];
            $isItem = $node instanceof Item;
            array_push($rows, ...$this->rows(
                $node->children,
                $isItem ? [...$ancestors, $key] : $ancestors,
                $key,
            ));
        }

        return $rows;
    }
}

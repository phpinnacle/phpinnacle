<?php

namespace PHPinnacle\Common\Forms;

use Closure;
use Filament\Forms\Components\Concerns\HasNestedRecursiveValidationRules;
use Filament\Forms\Components\Contracts\HasNestedRecursiveValidationRules as HasNestedRecursiveValidationRulesContract;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Concerns\CanBeCompact;
use Filament\Support\Components\Attributes\ExposedLivewireMethod;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use Livewire\Attributes\Renderless;
use PHPinnacle\Common\Enums\ItemPickerMode;
use PHPinnacle\Common\Forms\ItemPicker\ItemTree;

/**
 * @phpstan-import-type PickerRow from ItemTree
 * @phpstan-type PickerNode array{key: string, value: ?string, group: bool, disabled: bool, selectable: bool, branch: bool, ancestors: list<string>, parent: ?string}
 * @phpstan-type PickerData array{nodes: list<PickerNode>, html: string, selectionNodes: list<PickerNode>, selectionHtml: string, empty: bool, limit: ?int, searchable: bool, hasResolver: bool, disabled: bool}
 */
class ItemPicker extends Field implements HasNestedRecursiveValidationRulesContract
{
    use CanBeCompact;
    use HasNestedRecursiveValidationRules;

    protected string $view = 'phpinnacle-common::components.item-picker';

    /** @var list<Item|ItemGroup>|Closure */
    protected array|Closure $items = [];

    /** @var ?Closure(list<string>|string|null, list<Item|ItemGroup>): list<Item> */
    protected ?Closure $resolveUsing = null;

    protected bool|Closure $isSearchable = false;

    protected ?int $itemsLimit = null;

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

    public function searchable(bool|Closure $condition = true): self
    {
        $this->isSearchable = $condition;

        return $this;
    }

    public function limit(?int $limit): self
    {
        if ($limit !== null && $limit < 1) {
            throw new InvalidArgumentException('The item limit must be greater than zero.');
        }

        $this->itemsLimit = $limit;

        return $this;
    }

    /** @param Closure(list<string>|string|null, list<Item|ItemGroup>): list<Item> $callback */
    public function resolve(Closure $callback): self
    {
        $this->resolveUsing = $callback;

        return $this;
    }

    public function isSearchable(): bool
    {
        return (bool) $this->evaluate($this->isSearchable);
    }

    public function getLimit(): ?int
    {
        return $this->itemsLimit;
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
        return $this->evaluateItems(null, 0, $this->pickerState(...));
    }

    /** @return list<PickerRow> */
    public function getRows(): array
    {
        return new ItemTree($this->getItems())->rows();
    }

    /** @return list<string> */
    public function getSelectableValues(): array
    {
        $values = [];
        if ($this->resolveUsing !== null) {
            $state = $this->pickerState();
            $items = $this->evaluateItems(null, 0, $state);
            $available = $this->resolveItems($state, $items);
        } else {
            $items = is_array($this->items) ? $this->items : $this->getItems();
            $available = new ItemTree($items)->items();
        }

        foreach ($available as $item) {
            if ($this->isItemSelectable($item)) {
                $values[] = $item->value;
            }
        }

        return $values;
    }

    /** @return PickerData */
    #[ExposedLivewireMethod]
    #[Renderless]
    public function getItemsForJs(mixed $search, mixed $offset, mixed $state): array
    {
        /** @var array{search: ?string, offset: int|numeric-string, state: list<string>|string|null} $validated */
        $validated = Validator::make(
            ['search' => $search, 'offset' => $offset, 'state' => $state],
            [
                'search' => ['nullable', 'string'],
                'offset' => ['required', 'integer', 'min:0'],
                ...$this->pickerStateRules(),
            ],
        )->validate();

        return $this->pickerData(
            $this->isSearchable() && $validated['search'] !== '' ? $validated['search'] : null,
            $this->itemsLimit === null ? 0 : (int) $validated['offset'],
            $validated['state'],
        );
    }

    /** @return array{nodes: list<PickerNode>, html: string} */
    #[ExposedLivewireMethod]
    #[Renderless]
    public function getSelectionForJs(mixed $state): array
    {
        /** @var array{state: list<string>|string|null} $validated */
        $validated = Validator::make(['state' => $state], $this->pickerStateRules())->validate();
        $items = $this->evaluateItems(null, 0, $validated['state']);

        return $this->selectionData($this->resolveItems($validated['state'], $items));
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

    /** @return array{pickerData: PickerData, id: string, select: bool, disabled: bool, multiple: bool} */
    private function pickerViewData(): array
    {
        return [
            'pickerData' => $this->pickerData(null, 0, $this->pickerState()),
            'id' => $this->getId(),
            'select' => $this->getMode() === ItemPickerMode::Select,
            'disabled' => $this->isDisabled(),
            'multiple' => $this->isMultiple(),
        ];
    }

    /** @return list<string>|string|null */
    private function pickerState(): array|string|null
    {
        /** @var array{state: list<string>|string|null} $validated */
        $validated = Validator::make(['state' => $this->getState()], $this->pickerStateRules())->validate();

        return $validated['state'];
    }

    /** @return array<string, list<string>> */
    private function pickerStateRules(): array
    {
        return (
            $this->isMultiple()
                ? ['state' => ['nullable', 'array', 'list'], 'state.*' => ['string', 'distinct:strict']]
                : ['state' => ['nullable', 'string']]
        );
    }

    /**
     * @param list<string>|string|null|Closure $state
     * @return list<Item|ItemGroup>
     */
    private function evaluateItems(?string $search, int $offset, array|string|Closure|null $state): array
    {
        if (is_array($this->items)) {
            return $this->itemsLimit === null ? $this->items : array_slice($this->items, $offset, $this->itemsLimit);
        }

        return $this->evaluate($this->items, [
            'search' => $search,
            'state' => $state,
            ...($this->itemsLimit === null ? [] : ['offset' => $offset, 'limit' => $this->itemsLimit]),
        ]);
    }

    /**
     * @param list<string>|string|null $state
     * @param list<Item|ItemGroup> $items
     * @return list<Item>
     */
    private function resolveItems(array|string|null $state, array $items): array
    {
        if ($this->resolveUsing !== null) {
            return ($this->resolveUsing)($state, $items);
        }

        $resolved = [];

        $values = match (true) {
            is_array($state) => $state,
            $state === null => [],
            default => [$state],
        };

        foreach (new ItemTree(is_array($this->items) ? $this->items : $items)->items() as $item) {
            if (is_array($this->items) && $this->itemsLimit !== null && !in_array($item->value, $values, true)) {
                continue;
            }

            $resolved[] = $item;
        }

        return $resolved;
    }

    /**
     * @param list<string>|string|null $state
     * @return PickerData
     */
    private function pickerData(?string $search, int $offset, array|string|null $state): array
    {
        $items = $this->evaluateItems($search, $offset, $state);
        $resolved = $this->resolveItems($state, $items);
        $tree = new ItemTree(
            $this->resolveUsing !== null && $this->isDisabled() && $this->getMode() === ItemPickerMode::Inline
                ? $resolved
                : $items,
        );
        $rows = $tree->rows($this->itemsLimit === null ? '' : '-page-' . $offset);
        $selection = $this->selectionData($resolved);

        return [
            ...$this->renderRows('item-picker-rows', $rows),
            'selectionNodes' => $selection['nodes'],
            'selectionHtml' => $selection['html'],
            'empty' => $items === [],
            'limit' => $this->itemsLimit,
            'searchable' => $this->isSearchable(),
            'hasResolver' => $this->resolveUsing !== null || is_array($this->items) && $this->itemsLimit !== null,
            'disabled' => $this->isDisabled(),
        ];
    }

    /**
     * @param list<Item> $items
     * @return array{nodes: list<PickerNode>, html: string}
     */
    private function selectionData(array $items): array
    {
        $rows = [];

        foreach ($items as $index => $item) {
            $rows[] = [
                'key' => '-selected-' . $index,
                'node' => $item,
                'depth' => 0,
                'ancestors' => [],
                'parent' => null,
            ];
        }

        return $this->renderRows('item-picker-selection', $rows);
    }

    /**
     * @param list<PickerRow> $rows
     * @return array{nodes: list<PickerNode>, html: string}
     */
    private function renderRows(string $view, array $rows): array
    {
        return [
            'nodes' => $this->nodes($rows),
            'html' => view('phpinnacle-common::components.' . $view, [
                ...$this->rowViewData(),
                'rows' => $rows,
            ])->render(),
        ];
    }

    /** @return array{id: string, select: bool, disabled: bool, multiple: bool, branchesSelectable: bool, isCompact: Closure, isCircular: Closure} */
    private function rowViewData(): array
    {
        return [
            'id' => $this->getId(),
            'select' => $this->getMode() === ItemPickerMode::Select,
            'disabled' => $this->isDisabled(),
            'multiple' => $this->isMultiple(),
            'branchesSelectable' => $this->canSelectBranches(),
            'isCompact' => $this->isCompact(...),
            'isCircular' => $this->isCircular(...),
        ];
    }

    /**
     * @param list<PickerRow> $rows
     * @return list<PickerNode>
     */
    private function nodes(array $rows): array
    {
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

        return $nodes;
    }
}

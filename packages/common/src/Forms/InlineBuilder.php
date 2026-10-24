<?php

namespace PHPinnacle\Common\Forms;

use Closure;
use Filament\Forms\Components\Concerns\HasPlaceholder;
use Filament\Forms\Components\Field;
use Filament\Schemas\Schema;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Translation\PotentiallyTranslatedString;
use Illuminate\Validation\Rule;
use PHPinnacle\Common\Forms\InlineBuilder\Block;
use PHPinnacle\Common\Forms\InlineBuilder\BuilderAction;

/** @phpstan-type BuilderItem array{type: string, data?: array<string, mixed>} */
class InlineBuilder extends Field
{
    use HasPlaceholder;

    protected string $view = 'phpinnacle-common::components.inline-builder';

    /** @var list<Block>|Closure */
    protected array|Closure $blocks = [];

    protected bool|Closure $isAddable = true;

    protected bool|Closure $isDeletable = true;

    protected bool|Closure $isReorderable = true;

    protected ?Closure $reorderableUsing = null;

    protected string|Closure|null $addActionLabel = null;

    /** @param list<Block>|Closure $blocks */
    public function blocks(array|Closure $blocks): static
    {
        $this->blocks = $blocks;

        return $this;
    }

    /** @return array<string, Block> */
    public function getBlocks(): array
    {
        $blocks = [];
        foreach ($this->evaluate($this->blocks) as $block) {
            $blocks[$block->getName()] = $block;
        }

        return $blocks;
    }

    /** @return array<string, Block> */
    public function getAvailableBlocks(): array
    {
        return (
            $this->isAddable()
                ? array_filter($this->getBlocks(), fn (Block $block) => $block->isAddable($this))
                : []
        );
    }

    public function addable(bool|Closure $condition = true): static
    {
        $this->isAddable = $condition;

        return $this;
    }

    public function isAddable(): bool
    {
        return !$this->isDisabled() && $this->evaluate($this->isAddable);
    }

    public function deletable(bool|Closure $condition = true): static
    {
        $this->isDeletable = $condition;

        return $this;
    }

    public function isDeletable(): bool
    {
        return !$this->isDisabled() && $this->evaluate($this->isDeletable);
    }

    public function reorderable(bool|Closure $condition = true): static
    {
        $this->isReorderable = $condition;

        return $this;
    }

    public function isReorderable(): bool
    {
        return !$this->isDisabled() && $this->evaluate($this->isReorderable);
    }

    /** @param Closure $callback Receives the proposed list of items as $items. */
    public function reorderableUsing(Closure $callback): static
    {
        $this->reorderableUsing = $callback;

        return $this;
    }

    /** @param list<BuilderItem> $items */
    public function isOrderAllowed(array $items): bool
    {
        return $this->isReorderable() && ($this->reorderableUsing === null || $this->evaluate($this->reorderableUsing, [
            'items' => $items,
        ]));
    }

    public function addActionLabel(string|Closure|null $label): static
    {
        $this->addActionLabel = $label;

        return $this;
    }

    public function getAddActionLabel(): string
    {
        return $this->evaluate($this->addActionLabel) ?? __('phpinnacle-common::inline-builder.add');
    }

    public function getEditorActionName(): string
    {
        return 'inlineBuilder';
    }

    /** @return list<BuilderItem> */
    public function getItems(): array
    {
        return $this->getRawState() ?? [];
    }

    public function getItemSchema(string $type, ?int $index): Schema
    {
        return $this
            ->makeChildSchema((string) $index)
            ->statePath(($index ?? count($this->getItems())) . '.data')
            ->components($this->getBlocks()[$type]->getSchema($this, $index));
    }

    /** @return list<list<int>> */
    public function getAllowedMoves(): array
    {
        $items = $this->getItems();
        $allowed = [];
        foreach ($items as $from => $item) {
            $positions = [];
            $remaining = $items;
            array_splice($remaining, $from, 1);
            foreach (array_keys($items) as $to) {
                $candidate = $remaining;
                array_splice($candidate, $to, 0, [$item]);
                if ($this->isOrderAllowed($candidate)) {
                    $positions[] = $to;
                }
            }
            $allowed[] = $positions;
        }

        return $allowed;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->default([]);
        $this->partiallyRenderAfterStateUpdated();
        $this->mutateDehydratedStateUsing(fn (self $component, array $state) => $component->dehydrateItems($state));
        $this->rule('bail');
        $this->rules(fn (self $component) => [$component->validateItems(...)]);
        $this->registerActions([BuilderAction::make(...)]);
    }

    /**
     * @param list<BuilderItem> $state
     * @return list<BuilderItem>
     */
    private function dehydrateItems(array $state): array
    {
        foreach ($state as $index => $item) {
            $parameters = $this->getItemSchema($item['type'], $index);
            $parametersState = [];
            Arr::set($parametersState, $parameters->getStatePath(), $item['data'] ?? []);
            $parameters->dehydrateState($parametersState);
            $parameters->mutateDehydratedState($parametersState);
            $state[$index] = [
                'type' => $item['type'],
                'data' => Arr::get($parametersState, $parameters->getStatePath(), []),
            ];
        }

        return $state;
    }

    /** @param Closure(string): PotentiallyTranslatedString $fail */
    private function validateItems(string $attribute, mixed $value, Closure $fail): void
    {
        $validator = Validator::make(['items' => $value], [
            'items' => ['array', 'list'],
            'items.*' => ['array:type,data'],
            'items.*.type' => ['required', 'string', Rule::in(array_keys($this->getBlocks()))],
            'items.*.data' => ['sometimes', 'array'],
        ]);
        if ($validator->fails()) {
            $fail($validator->errors()->first())->translate();

            return;
        }
        /** @var list<BuilderItem> $items */
        $items = $validator->validated()['items'];
        foreach ($items as $index => $item) {
            $this->getItemSchema($item['type'], $index)->validate();
        }
    }
}

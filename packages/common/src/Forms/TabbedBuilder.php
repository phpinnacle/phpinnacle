<?php

namespace PHPinnacle\Common\Forms;

use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Builder;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use PHPinnacle\Common\Enums\TabPosition;
use PHPinnacle\Common\Forms\TabbedBuilder\Block;

class TabbedBuilder extends Builder
{
    protected string $view = 'phpinnacle-common::components.tabbed-builder';

    protected TabPosition|Closure $tabsPosition = TabPosition::Top;

    protected string|BackedEnum|Htmlable|Closure|null $emptyStateIcon = null;

    protected string|Htmlable|Closure|null $emptyStateHeading = null;

    protected string|Htmlable|Closure|null $emptyStateDescription = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->deleteAction(null);
    }

    public function getAddActionLabel(): string
    {
        return $this->evaluate($this->addActionLabel) ?? __('phpinnacle-common::tabbed-builder.add');
    }

    public function tabsPosition(TabPosition|Closure $position): static
    {
        $this->tabsPosition = $position;

        return $this;
    }

    public function getTabsPosition(): TabPosition
    {
        return $this->evaluate($this->tabsPosition);
    }

    public function emptyStateIcon(string|BackedEnum|Htmlable|Closure|null $icon): static
    {
        $this->emptyStateIcon = $icon;

        return $this;
    }

    public function getEmptyStateIcon(): string|BackedEnum|Htmlable|null
    {
        return $this->evaluate($this->emptyStateIcon);
    }

    public function emptyStateHeading(string|Htmlable|Closure|null $heading): static
    {
        $this->emptyStateHeading = $heading;

        return $this;
    }

    public function getEmptyStateHeading(): string|Htmlable
    {
        return $this->evaluate($this->emptyStateHeading) ?? __('phpinnacle-common::tabbed-builder.empty');
    }

    public function emptyStateDescription(string|Htmlable|Closure|null $description): static
    {
        $this->emptyStateDescription = $description;

        return $this;
    }

    public function getEmptyStateDescription(): string|Htmlable|null
    {
        return $this->evaluate($this->emptyStateDescription);
    }

    /** @return array<Block> */
    public function getBlockPickerBlocks(): array
    {
        /** @var array<Block> $blocks */
        $blocks = parent::getBlockPickerBlocks();

        return array_filter($blocks, fn (Block $block) => $block->isAddable());
    }

    public function getAddAction(): Action
    {
        return parent::getAddAction()
            ->before(function (self $component, array $arguments) {
                Validator::make($arguments, [
                    'block' => [
                        'required',
                        'string',
                        Rule::in(
                            $component->isAddable()
                                ? array_map(fn (Block $block) => $block->getName(), $component->getBlockPickerBlocks())
                                : [],
                        ),
                    ],
                ])->validate();
            });
    }

    public function deleteAction(?Closure $callback): static
    {
        return parent::deleteAction(function (Action $action, self $component) use ($callback) {
            $action->icon('heroicon-m-x-mark')->color('gray');

            return $callback ? $component->evaluate($callback, ['action' => $action]) : null;
        });
    }

    public function getDeleteAction(): Action
    {
        return parent::getDeleteAction()
            ->before(function (self $component, array $arguments) {
                Validator::make($arguments, [
                    'item' => [
                        'required',
                        'string',
                        Rule::in(
                            $component->isDeletable()
                                ? array_keys($component->getRawState())
                                : [],
                        ),
                    ],
                ])->validate();
            });
    }

    public function isDeletable(): bool
    {
        return parent::isDeletable() && $this->getItemsCount() > ($this->getMinItems() ?? 0);
    }

    public function getReorderAction(): Action
    {
        return parent::getReorderAction()
            ->before(function (self $component, array $arguments) {
                Validator::make($arguments, [
                    'items' => ['present', 'array', 'list', 'size:' . $component->getItemsCount()],
                    'items.*' => [
                        'required',
                        'string',
                        'distinct',
                        Rule::in(
                            $component->isReorderable()
                                ? array_keys($component->getRawState())
                                : [],
                        ),
                    ],
                ])->validate();
            });
    }

    /** @param array<string, array<mixed>> $rules */
    public function dehydrateValidationRules(array &$rules): void
    {
        parent::dehydrateValidationRules($rules);

        $path = $this->getStatePath();
        $rules[$path][] = 'array';
        $rules["{$path}.*"] = ['array:type,data'];
        $rules["{$path}.*.type"] = [
            'required',
            'string',
            Rule::in(array_map(
                fn (\Filament\Forms\Components\Builder\Block $block) => $block->getName(),
                $this->getBlocks(),
            )),
        ];
        $rules["{$path}.*.data"] = ['sometimes', 'array'];
    }
}

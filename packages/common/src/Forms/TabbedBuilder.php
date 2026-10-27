<?php

namespace PHPinnacle\Common\Forms;

use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Builder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use PHPinnacle\Common\Forms\TabbedBuilder\Block;

class TabbedBuilder extends Builder
{
    protected string $view = 'phpinnacle-common::components.tabbed-builder';

    protected bool|Closure $isVertical = false;

    public function getAddActionLabel(): string
    {
        return $this->evaluate($this->addActionLabel) ?? __('phpinnacle-common::tabbed-builder.add');
    }

    public function vertical(bool|Closure $condition = true): static
    {
        $this->isVertical = $condition;

        return $this;
    }

    public function isVertical(): bool
    {
        return $this->evaluate($this->isVertical);
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

    public function getDeleteAction(): Action
    {
        return parent::getDeleteAction()
            ->icon('heroicon-m-x-mark')
            ->color('gray')
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

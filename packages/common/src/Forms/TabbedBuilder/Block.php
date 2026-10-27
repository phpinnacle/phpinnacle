<?php

namespace PHPinnacle\Common\Forms\TabbedBuilder;

use Closure;

class Block extends \Filament\Forms\Components\Builder\Block
{
    protected bool|Closure $isAddable = true;

    public function addable(bool|Closure $condition = true): static
    {
        $this->isAddable = $condition;

        return $this;
    }

    public function isAddable(): bool
    {
        return $this->evaluate($this->isAddable);
    }
}

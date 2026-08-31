<?php

namespace PHPinnacle\Tempo\Filters;

use Closure;
use Filament\Tables\Filters\Filter;

abstract class RangeFilter extends Filter
{
    /** @var (Closure(): string)|string|null */
    protected Closure|string|null $column = null;

    /** @param (Closure(): string)|string|null $column */
    public function column(Closure|string|null $column): static
    {
        $this->column = $column;

        return $this;
    }

    public function getColumn(): string
    {
        if ($this->column === null) {
            return $this->getName();
        }

        return $this->evaluate($this->column);
    }
}

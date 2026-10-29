<?php

namespace PHPinnacle\Eidos\Forms\Concerns;

use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Field;

/** @phpstan-require-extends Field */
trait HasHeaderActions
{
    /** @var array<Action>|(Closure(): array<Action>) */
    protected array|Closure $headerActions = [];

    /** @param array<Action>|Closure $actions */
    public function headerActions(array|Closure $actions): static
    {
        $this->headerActions = $actions;
        $this->registerActions([fn () => $this->evaluate($this->headerActions)]);

        return $this;
    }

    /** @return array<Action> */
    public function getHeaderActions(): array
    {
        $names = array_map(fn (Action $action) => $action->getName(), $this->evaluate($this->headerActions));

        return array_values(array_filter($this->getActions(), fn (Action $action) => in_array(
            $action->getName(),
            $names,
            true,
        )));
    }
}

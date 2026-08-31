<?php

namespace PHPinnacle\Tempo\StateCasts;

use Filament\Schemas\Components\StateCasts\Contracts\StateCast;
use Filament\Schemas\Components\StateCasts\DateTimeStateCast;

readonly class DateRangeStateCast implements StateCast
{
    public function __construct(
        private DateTimeStateCast $inner,
        private string $separator,
    ) {}

    public function get(mixed $state): ?array
    {
        if (blank($state)) {
            return null;
        }

        if (is_string($state)) {
            $state = explode($this->separator, $state);
        }

        if (!is_array($state) || count($state) !== 2) {
            return null;
        }

        return array_map(fn (mixed $date) => $this->inner->get($date), $state);
    }

    public function set(mixed $state): ?array
    {
        return $this->get($state);
    }
}

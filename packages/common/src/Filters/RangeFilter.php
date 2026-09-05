<?php

namespace PHPinnacle\Common\Filters;

use Closure;
use Filament\Forms\Components\Slider;
use Filament\Forms\Components\Slider\Enums\PipsMode;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class RangeFilter extends Filter
{
    private Closure|int $minValue = 0;

    private Closure|int $maxValue = 100;

    private Closure|int $step = 1;

    public function setUp(): void
    {
        parent::setUp();

        $this->schema(fn () => [
            Slider::make('range')
                ->label($this->getLabel())
                ->range($this->minValue, $this->maxValue)
                ->default([$this->minValue, $this->maxValue])
                ->step($this->step)
                ->fillTrack([false, true, false])
                ->pips(PipsMode::Count)
                ->pipsValues(5),
        ]);

        $this->query(function (Builder $query, array $data) {
            $range = $data['range'] ?? [];

            if ($range === []) {
                return $query;
            }

            return $query->whereBetween($this->getName(), $range);
        });
    }

    public function minValue(Closure|int $value): static
    {
        $this->minValue = $value;

        return $this;
    }

    public function maxValue(Closure|int $value): static
    {
        $this->maxValue = $value;

        return $this;
    }

    public function step(Closure|int $value): static
    {
        $this->step = $value;

        return $this;
    }
}

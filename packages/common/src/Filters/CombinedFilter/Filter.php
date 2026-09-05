<?php

namespace PHPinnacle\Common\Filters\CombinedFilter;

use Closure;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Concerns\EvaluatesClosures;
use Filament\Tables\Filters\Indicator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class Filter
{
    use EvaluatesClosures;

    public array $depends = [];

    private Collection|Closure|array $options = [];

    private ?Closure $applyUsing = null;

    private ?string $label = null;

    public function __construct(
        public string $name,
    ) {}

    public static function make(string $name): self
    {
        return new self($name);
    }

    public function apply(Builder $query, array $data): void
    {
        $value = $data[$this->name] ?? null;

        if (blank($value)) {
            return;
        }

        if ($this->applyUsing !== null) {
            $this->evaluate(
                $this->applyUsing,
                [
                    'query' => $query,
                    'field' => $this->name,
                    'value' => $value,
                ],
                [
                    $query::class => $query,
                ],
            );
        } else {
            $query->where($this->name, $value);
        }
    }

    public function applyUsing(Closure $applyUsing): self
    {
        $this->applyUsing = $applyUsing;

        return $this;
    }

    public function depends(array $depends): self
    {
        $this->depends = $depends;

        return $this;
    }

    public function indicator(array $data, array $options): ?Indicator
    {
        $value = $data[$this->name] ?? null;

        if (blank($value)) {
            return null;
        }

        $item = $options[$this->name][$value] ?? null;

        return $item !== null
            ? Indicator::make(sprintf('%s: %s', $this->label, $item))->removeField($this->name)
            : null;
    }

    public function label(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function options(Closure|Collection|array $options): self
    {
        $this->options = $options;

        return $this;
    }

    public function select(array $dependencies): Select
    {
        return Select::make($this->name)
            ->key($this->name)
            ->label($this->label)
            ->options($this->options)
            ->reactive()
            ->afterStateUpdated(function (Set $set) use ($dependencies) {
                $dependents = $dependencies[$this->name] ?? [];

                foreach ($dependents as $dependent) {
                    $set($dependent, null);
                }
            })
            ->disabled(
                fn (Get $get) => $this->depends !== [] && array_any($this->depends, fn (string $depend) => $get->blank(
                    $depend,
                )),
            );
    }
}

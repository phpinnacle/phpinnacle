<?php

namespace PHPinnacle\Common\Forms\InlineBuilder;

use BackedEnum;
use Closure;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Concerns\HasDescription;
use Filament\Schemas\Components\Concerns\HasLabel;
use Filament\Schemas\Components\Concerns\HasName;
use Filament\Support\Concerns\HasColor;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;
use PHPinnacle\Common\Forms\InlineBuilder;

class Block extends \Filament\Support\Components\Component
{
    use HasColor;
    use HasDescription;
    use HasLabel;
    use HasName;

    protected string|BackedEnum|Htmlable|Closure|null $icon = null;

    /** @var array<Component>|Closure */
    protected array|Closure $schema = [];

    protected bool|Closure $isAddable = true;

    final public function __construct(string $name)
    {
        $this->name($name);
        $this->label(Str::headline($name));
        $this->defaultColor('primary');
    }

    public static function make(string $name): static
    {
        $block = new static($name);
        $block->configure();

        return $block;
    }

    public function icon(string|BackedEnum|Htmlable|Closure|null $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return $this->evaluate($this->icon);
    }

    /** @param array<Component>|Closure $schema */
    public function schema(array|Closure $schema): static
    {
        $this->schema = $schema;

        return $this;
    }

    public function hasSchema(): bool
    {
        return $this->schema !== [];
    }

    /** @return array<Component> */
    public function getSchema(InlineBuilder $component, ?int $index): array
    {
        return array_map(
            static fn (Component $field) => clone $field,
            $component->evaluate($this->schema, ['index' => $index]),
        );
    }

    public function addable(bool|Closure $condition = true): static
    {
        $this->isAddable = $condition;

        return $this;
    }

    public function isAddable(InlineBuilder $component): bool
    {
        return $component->evaluate($this->isAddable);
    }
}

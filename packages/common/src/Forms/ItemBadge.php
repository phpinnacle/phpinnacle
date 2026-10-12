<?php

namespace PHPinnacle\Common\Forms;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

final class ItemBadge implements HasLabel, HasColor, HasIcon
{
    public function __construct(
        public string|int $label,
        public string $color = 'gray',
        public string|BackedEnum|Htmlable|null $icon = null,
    ) {}

    public static function make(string|int $label): self
    {
        return new static($label);
    }

    public function label(string|int $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function color(string $color): self
    {
        $this->color = $color;

        return $this;
    }

    public function icon(string|BackedEnum|Htmlable|null $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function getLabel(): string
    {
        return (string) $this->label;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return $this->icon;
    }
}

<?php

namespace PHPinnacle\Common\Forms;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;

final class Item implements HasColor, HasDescription, HasIcon, HasLabel
{
    /** @var list<ItemBadge> */
    public array $badges = [];

    public string $label;

    /** @param list<Item|ItemGroup> $children */
    public function __construct(
        public readonly string $value,
        ?string $label = null,
        public ?string $description = null,
        public string|BackedEnum|Htmlable|null $icon = null,
        public string $color = 'primary',
        public array $children = [],
        public bool $disabled = false,
        public ?string $image = null,
    ) {
        $this->label = $label ?? Str::title($value);
    }

    public static function make(string $value): self
    {
        return new static($value);
    }

    public function label(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function description(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function icon(string|BackedEnum|Htmlable|null $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function image(?string $image): self
    {
        $this->image = $image;

        return $this;
    }

    public function color(string $color): self
    {
        $this->color = $color;

        return $this;
    }

    /** @param list<Item|ItemGroup> $children */
    public function children(array $children): self
    {
        $this->children = $children;

        return $this;
    }

    public function disabled(bool $condition = true): self
    {
        $this->disabled = $condition;

        return $this;
    }

    /** @param list<ItemBadge> $badges */
    public function badges(array $badges): self
    {
        $this->badges = $badges;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return $this->icon;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function getColor(): string
    {
        return $this->color;
    }
}

<?php

namespace PHPinnacle\Common\Forms;

use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Support\Str;

final class ItemGroup implements HasLabel, HasDescription
{
    /** @var list<ItemBadge> */
    public array $badges = [];

    /** @param list<Item|ItemGroup> $children */
    public function __construct(
        public string $label,
        public array $children = [],
        public ?string $description = null,
    ) {}

    public static function make(string $value): self
    {
        return new static(Str::title($value));
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

    /** @param list<Item|ItemGroup> $children */
    public function children(array $children): self
    {
        $this->children = $children;

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
}

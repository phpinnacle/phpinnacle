<?php

namespace PHPinnacle\Chronica\Timeline;

use Closure;

class Relation
{
    public function __construct(
        public string $name,
        public string $class,
        public ?string $label = null,
        public Closure|string|null $title = null,
    ) {}

    public static function make(string $name, string $class): self
    {
        return new self($name, $class);
    }

    public function label(string $label, ?int $case = null): self
    {
        $this->label = $case !== null ? mb_convert_case($label, $case) : $label;

        return $this;
    }

    public function title(string $title): self
    {
        $this->title = $title;

        return $this;
    }
}

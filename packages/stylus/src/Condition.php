<?php

namespace PHPinnacle\Stylus;

use BackedEnum;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;
use PHPinnacle\Stylus\Contracts\Condition as ConditionContract;
use PHPinnacle\Stylus\Enums\ConditionKind;

class Condition implements ConditionContract
{
    /**
     * @param  list<string>  $types
     * @param  string|array<string>|null  $color
     */
    private function __construct(
        public readonly string $name,
        public readonly string $expression,
        public readonly ConditionKind $type,
        public readonly string $label,
        public readonly array $types = [],
        public readonly bool $matchesVariableTypes = false,
        public readonly string|BackedEnum|Htmlable|null $icon = null,
        public readonly ?string $description = null,
        public readonly string|array|null $color = null,
    ) {}

    public static function comparison(string $name, string $expression): self
    {
        return new self(
            $name,
            $expression,
            ConditionKind::Comparison,
            Str::headline($name),
            matchesVariableTypes: true,
        );
    }

    public static function truthy(): self
    {
        return new self('truthy', '', ConditionKind::Truthy, 'True', types: ['boolean']);
    }

    public static function test(string $name): self
    {
        return new self($name, $name, ConditionKind::Test, Str::headline($name));
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getExpression(): string
    {
        return $this->expression;
    }

    public function getType(): ConditionKind
    {
        return $this->type;
    }

    /** @return list<string> */
    public function getTypes(): array
    {
        return $this->types;
    }

    public function matchesVariableTypes(): bool
    {
        return $this->matchesVariableTypes;
    }

    public function label(string $label): self
    {
        return new self(
            $this->name,
            $this->expression,
            $this->type,
            $label,
            $this->types,
            $this->matchesVariableTypes,
            $this->icon,
            $this->description,
            $this->color,
        );
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function icon(string|BackedEnum|Htmlable|null $icon): self
    {
        return new self(
            $this->name,
            $this->expression,
            $this->type,
            $this->label,
            $this->types,
            $this->matchesVariableTypes,
            $icon,
            $this->description,
            $this->color,
        );
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return $this->icon;
    }

    public function description(?string $description): self
    {
        return new self(
            $this->name,
            $this->expression,
            $this->type,
            $this->label,
            $this->types,
            $this->matchesVariableTypes,
            $this->icon,
            $description,
            $this->color,
        );
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    /** @param string|array<string>|null $color */
    public function color(string|array|null $color): self
    {
        return new self(
            $this->name,
            $this->expression,
            $this->type,
            $this->label,
            $this->types,
            $this->matchesVariableTypes,
            $this->icon,
            $this->description,
            $color,
        );
    }

    /** @return string|array<string>|null */
    public function getColor(): string|array|null
    {
        return $this->color;
    }

    public function types(string ...$types): self
    {
        return new self(
            $this->name,
            $this->expression,
            $this->type,
            $this->label,
            array_values($types),
            $this->matchesVariableTypes,
            $this->icon,
            $this->description,
            $this->color,
        );
    }

    public function supports(string $variableType): bool
    {
        return $this->types === [] || in_array($variableType, $this->types, true);
    }

    public function matchVariableTypes(bool $condition = true): self
    {
        return new self(
            $this->name,
            $this->expression,
            $this->type,
            $this->label,
            $this->types,
            $condition,
            $this->icon,
            $this->description,
            $this->color,
        );
    }

    public function getKey(): string
    {
        return $this->type->key($this->name);
    }
}

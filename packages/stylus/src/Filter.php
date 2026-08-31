<?php

namespace PHPinnacle\Stylus;

use BackedEnum;
use Closure;
use Filament\Schemas\Components\Component;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;
use PHPinnacle\Stylus\Contracts\Filter as FilterContract;
use PHPinnacle\Stylus\Enums\FilterOutput;

class Filter implements FilterContract
{
    /** @var list<string> */
    public readonly array $types;

    /** @var list<Component> */
    public readonly array $schema;

    /** @var (Closure(array<string, mixed>): list<string>)|null */
    private readonly ?Closure $argumentsUsing;

    /**
     * @param  list<string>  $types
     * @param  list<Component>  $schema
     * @param  (Closure(array<string, mixed>): list<string>)|null  $argumentsUsing
     * @param  string|array<string>|null  $color
     */
    public function __construct(
        public readonly string $name,
        public readonly string $label,
        array $types = [],
        array $schema = [],
        ?Closure $argumentsUsing = null,
        public readonly bool $supportsBlocks = false,
        public readonly string|BackedEnum|Htmlable|null $icon = null,
        public readonly ?string $description = null,
        public readonly string|array|null $color = null,
        public readonly FilterOutput $output = FilterOutput::Same,
    ) {
        $this->types = $types;
        $this->schema = $schema;
        $this->argumentsUsing = $argumentsUsing;
    }

    public static function make(string $name): self
    {
        return new self($name, Str::headline($name));
    }

    /** @param Closure(array<string, mixed>): list<string> $callback */
    public function argumentsUsing(Closure $callback): self
    {
        return new self(
            $this->name,
            $this->label,
            $this->types,
            $this->schema,
            $callback,
            $this->supportsBlocks,
            $this->icon,
            $this->description,
            $this->color,
            $this->output,
        );
    }

    public function blocks(bool $condition = true): self
    {
        return new self(
            $this->name,
            $this->label,
            $this->types,
            $this->schema,
            $this->argumentsUsing,
            $condition,
            $this->icon,
            $this->description,
            $this->color,
            $this->output,
        );
    }

    /** @param string|array<string>|null $color */
    public function color(string|array|null $color): self
    {
        return new self(
            $this->name,
            $this->label,
            $this->types,
            $this->schema,
            $this->argumentsUsing,
            $this->supportsBlocks,
            $this->icon,
            $this->description,
            $color,
            $this->output,
        );
    }

    public function description(?string $description): self
    {
        return new self(
            $this->name,
            $this->label,
            $this->types,
            $this->schema,
            $this->argumentsUsing,
            $this->supportsBlocks,
            $this->icon,
            $description,
            $this->color,
            $this->output,
        );
    }

    /**
     * @param  array<string, mixed>  $configuration
     * @return list<string>
     */
    public function getArguments(array $configuration): array
    {
        if ($this->argumentsUsing) {
            return ($this->argumentsUsing)($configuration);
        }

        $arguments = Arr::flatten($configuration);
        $normalizedArguments = [];

        foreach ($arguments as $argument) {
            if (is_bool($argument)) {
                $normalizedArguments[] = $argument ? 'true' : 'false';

                continue;
            }

            if (!is_string($argument) && !is_int($argument) && !is_float($argument)) {
                if ($argument === null) {
                    continue;
                }

                throw new InvalidArgumentException(
                    "Twig filter [{$this->name}] arguments must be scalar Twig expressions.",
                );
            }

            $argument = trim((string) $argument);

            if ($argument !== '') {
                $normalizedArguments[] = $argument;
            }
        }

        return $normalizedArguments;
    }

    /** @return string|array<string>|null */
    public function getColor(): string|array|null
    {
        return $this->color;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return $this->icon;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getOutput(): FilterOutput
    {
        return $this->output;
    }

    /** @return list<Component> */
    public function getSchema(): array
    {
        return array_map(
            static fn (Component $component) => clone $component,
            $this->schema,
        );
    }

    /** @return list<string> */
    public function getTypes(): array
    {
        return $this->types;
    }

    public function icon(string|BackedEnum|Htmlable|null $icon): self
    {
        return new self(
            $this->name,
            $this->label,
            $this->types,
            $this->schema,
            $this->argumentsUsing,
            $this->supportsBlocks,
            $icon,
            $this->description,
            $this->color,
            $this->output,
        );
    }

    public function label(string $label): self
    {
        return new self(
            $this->name,
            $label,
            $this->types,
            $this->schema,
            $this->argumentsUsing,
            $this->supportsBlocks,
            $this->icon,
            $this->description,
            $this->color,
            $this->output,
        );
    }

    public function output(FilterOutput $output): self
    {
        return new self(
            $this->name,
            $this->label,
            $this->types,
            $this->schema,
            $this->argumentsUsing,
            $this->supportsBlocks,
            $this->icon,
            $this->description,
            $this->color,
            $output,
        );
    }

    /** @param list<Component> $schema */
    public function schema(array $schema): self
    {
        return new self(
            $this->name,
            $this->label,
            $this->types,
            $schema,
            $this->argumentsUsing,
            $this->supportsBlocks,
            $this->icon,
            $this->description,
            $this->color,
            $this->output,
        );
    }

    public function supports(string $variableType): bool
    {
        return $this->types === [] || in_array($variableType, $this->types, true);
    }

    public function supportsBlocks(): bool
    {
        return $this->supportsBlocks;
    }

    public function types(string ...$types): self
    {
        return new self(
            $this->name,
            $this->label,
            $types,
            $this->schema,
            $this->argumentsUsing,
            $this->supportsBlocks,
            $this->icon,
            $this->description,
            $this->color,
            $this->output,
        );
    }
}

<?php

namespace PHPinnacle\Stylus;

use BackedEnum;
use Filament\Schemas\View\Components\IconComponent;
use Filament\Support\View\ComponentAttributeBag as FilamentComponentAttributeBag;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;
use PHPinnacle\Stylus\Contracts\Variable as VariableContract;

use function Filament\Support\generate_icon_html;

class Variable implements VariableContract
{
    /**
     * @param  array<string, VariableContract>  $properties
     * @param  string|array<string>|null  $color
     */
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly ?string $label = null,
        public readonly ?string $group = null,
        public readonly array $properties = [],
        public readonly ?VariableContract $item = null,
        public readonly string $keyType = 'mixed',
        public readonly ?string $sample = null,
        public readonly string|BackedEnum|Htmlable|null $icon = null,
        public readonly ?string $description = null,
        public readonly string|array|null $color = null,
    ) {}

    public static function make(string $name, string $type): self
    {
        return new self(
            $name,
            $type,
            Str::of($name)->afterLast('.')->headline()->toString(),
        );
    }

    public static function text(string $name): self
    {
        return self::make($name, 'text');
    }

    public static function collection(string $name): self
    {
        return self::make($name, 'collection');
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getGroup(): ?string
    {
        return $this->group;
    }

    /** @return array<string, VariableContract> */
    public function getProperties(): array
    {
        return $this->properties;
    }

    public function getItem(): ?VariableContract
    {
        return $this->item;
    }

    public function getKeyType(): string
    {
        return $this->keyType;
    }

    public function getSample(): ?string
    {
        return $this->sample;
    }

    public function label(string $label): self
    {
        return new self(
            $this->name,
            $this->type,
            $label,
            $this->group,
            $this->properties,
            $this->item,
            $this->keyType,
            $this->sample,
            $this->icon,
            $this->description,
            $this->color,
        );
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function icon(string|BackedEnum|Htmlable|null $icon): self
    {
        return new self(
            $this->name,
            $this->type,
            $this->label,
            $this->group,
            $this->properties,
            $this->item,
            $this->keyType,
            $this->sample,
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
            $this->type,
            $this->label,
            $this->group,
            $this->properties,
            $this->item,
            $this->keyType,
            $this->sample,
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
            $this->type,
            $this->label,
            $this->group,
            $this->properties,
            $this->item,
            $this->keyType,
            $this->sample,
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

    public function group(string $group): self
    {
        return new self(
            $this->name,
            $this->type,
            $this->label,
            $group,
            $this->properties,
            $this->item,
            $this->keyType,
            $this->sample,
            $this->icon,
            $this->description,
            $this->color,
        );
    }

    public function properties(VariableContract ...$properties): self
    {
        $indexedProperties = [];

        foreach ($properties as $property) {
            $indexedProperties[$property->getName()] = $property;
        }

        return new self(
            $this->name,
            $this->type,
            $this->label,
            $this->group,
            $indexedProperties,
            $this->item,
            $this->keyType,
            $this->sample,
            $this->icon,
            $this->description,
            $this->color,
        );
    }

    public function items(VariableContract $item, string $keyType = 'mixed'): self
    {
        return new self(
            $this->name,
            $this->type,
            $this->label,
            $this->group,
            $this->properties,
            $item,
            $keyType,
            $this->sample,
            $this->icon,
            $this->description,
            $this->color,
        );
    }

    public function sample(string $sample): self
    {
        return new self(
            $this->name,
            $this->type,
            $this->label,
            $this->group,
            $this->properties,
            $this->item,
            $this->keyType,
            $sample,
            $this->icon,
            $this->description,
            $this->color,
        );
    }

    public function isCollection(): bool
    {
        return $this->item !== null;
    }

    /** @return array<string, VariableContract> */
    public function flatten(?string $name = null, ?string $group = null): array
    {
        $name ??= $this->getName();
        $variable = $this->rebase($name, $group ?? $this->getGroup());
        $variables = [$name => $variable];

        foreach ($this->getProperties() as $property) {
            $variables = [
                ...$variables,
                ...$property->flatten("{$name}.{$property->getName()}", $property->getGroup() ?? $variable->getGroup()),
            ];
        }

        return $variables;
    }

    public function rebase(string $name, ?string $group = null): self
    {
        return new self(
            $name,
            $this->type,
            $this->label,
            $group,
            $this->properties,
            $this->item,
            $this->keyType,
            $this->sample,
            $this->icon,
            $this->description,
            $this->color,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'label' => $this->getLabel(),
            'group' => $this->group,
            'properties' => array_map(
                static fn (VariableContract $property) => $property->toArray(),
                array_values($this->getProperties()),
            ),
            'item' => $this->getItem()?->toArray(),
            'keyType' => $this->keyType,
            'sample' => $this->sample,
            'description' => $this->getDescription(),
            'iconHtml' => generate_icon_html(
                $this->icon,
                attributes: new FilamentComponentAttributeBag()
                    ->class(['fi-stylus-twig-metadata-icon'])
                    ->color(IconComponent::class, $this->color),
            )?->toHtml(),
        ];
    }
}

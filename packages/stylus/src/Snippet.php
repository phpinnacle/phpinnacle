<?php

namespace PHPinnacle\Stylus;

use BackedEnum;
use Filament\Schemas\View\Components\IconComponent;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\View\ComponentAttributeBag as FilamentComponentAttributeBag;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;

use function Filament\Support\generate_icon_html;

class Snippet implements HasColor, HasDescription, HasIcon, HasLabel
{
    /**
     * @param  list<array<string, mixed>>  $content
     * @param  list<string>  $requiredVariables
     * @param  string|array<string>|null  $color
     */
    public function __construct(
        public readonly string $name,
        public readonly string $label,
        public readonly array $content,
        public readonly array $requiredVariables = [],
        public readonly string|BackedEnum|Htmlable|null $icon = null,
        public readonly ?string $description = null,
        public readonly string|array|null $color = null,
    ) {}

    /** @param list<array<string, mixed>> $content */
    public static function make(string $name, array $content): self
    {
        return new self($name, Str::headline($name), $content);
    }

    public function label(string $label): self
    {
        return new self(
            $this->name,
            $label,
            $this->content,
            $this->requiredVariables,
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
            $this->label,
            $this->content,
            $this->requiredVariables,
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
            $this->label,
            $this->content,
            $this->requiredVariables,
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
            $this->label,
            $this->content,
            $this->requiredVariables,
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

    public function requires(string ...$variables): self
    {
        return new self(
            $this->name,
            $this->label,
            $this->content,
            $variables,
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
            'label' => $this->getLabel(),
            'content' => $this->content,
            'requiredVariables' => $this->requiredVariables,
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

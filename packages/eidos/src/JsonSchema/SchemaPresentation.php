<?php

namespace PHPinnacle\Eidos\JsonSchema;

use Filament\Support\Enums\IconSize;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;
use Illuminate\View\ComponentAttributeBag;

use function Filament\Support\generate_icon_html;

final class SchemaPresentation
{
    /**
     * @param array<string, mixed> $definition
     * @return array{definition: array<string, mixed>, propertyTitles: array<string, string>, icons: array<string, string>}
     */
    public static function render(array $definition, bool $withIcons = true): array
    {
        $titles = [];
        $icons = [];

        foreach (new Node($definition)->nodes() as $node) {
            $propertyName = $node->propertyName();

            if ($propertyName !== null) {
                $titles[$propertyName] = Str::apa(Str::headline($propertyName));
            }

            $iconName = $node->iconName();

            if (!$withIcons || $iconName === null || array_key_exists($iconName, $icons)) {
                continue;
            }

            /** @var Htmlable $icon */
            $icon = generate_icon_html(
                $iconName,
                attributes: new ComponentAttributeBag(['aria-hidden' => 'true']),
                size: IconSize::Small,
            );
            $icons[$iconName] = $icon->toHtml();
        }

        return ['definition' => $definition, 'propertyTitles' => $titles, 'icons' => $icons];
    }
}

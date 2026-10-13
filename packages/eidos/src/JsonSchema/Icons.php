<?php

namespace PHPinnacle\Eidos\JsonSchema;

use Filament\Support\Enums\IconSize;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\View\ComponentAttributeBag;

use function Filament\Support\generate_icon_html;

final class Icons
{
    /**
     * @param array<string, mixed> $schema
     * @return array<string, string>
     */
    public function render(array $schema): array
    {
        $icons = [];
        foreach (new Node($schema)->nodes() as $node) {
            $name = $node->iconName();

            if ($name === null || array_key_exists($name, $icons)) {
                continue;
            }

            /** @var Htmlable $icon */
            $icon = generate_icon_html(
                $name,
                attributes: new ComponentAttributeBag(['aria-hidden' => 'true']),
                size: IconSize::Small,
            );
            $icons[$name] = $icon->toHtml();
        }

        return $icons;
    }
}

<?php

namespace PHPinnacle\Common\JsonSchema;

use Illuminate\Support\Str;

final class PropertyTitles
{
    /**
     * @param array<string, mixed> $schema
     * @return array<string, string>
     */
    public function render(array $schema): array
    {
        $titles = [];
        foreach (new Node($schema)->nodes() as $node) {
            $name = $node->propertyName();

            if ($name !== null) {
                $titles[$name] = Str::apa(Str::headline($name));
            }
        }

        return $titles;
    }
}

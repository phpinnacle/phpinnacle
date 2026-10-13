<?php

namespace PHPinnacle\Common\JsonSchema;

use Illuminate\JsonSchema\Deserializer as LaravelDeserializer;
use Illuminate\JsonSchema\Types\Type;
use InvalidArgumentException;
use WeakMap;

/** @internal */
final class Deserializer extends LaravelDeserializer
{
    /** @var WeakMap<Type, array<string, mixed>> */
    private readonly WeakMap $extraAttributes;

    /** @param array<string, mixed> $root */
    protected function __construct(array $root)
    {
        parent::__construct($root);

        $this->extraAttributes = new WeakMap;
    }

    /** @param array<string, mixed> $schema */
    public static function deserialize(array $schema): Schema
    {
        $deserializer = new self($schema);

        return new Schema($deserializer->build($schema), $deserializer->extraAttributes);
    }

    /** @param array<string, mixed> $schema */
    protected function applyCommon(Type $type, array $schema): void
    {
        parent::applyCommon($type, $schema);

        $extraAttributes = [];

        foreach ($schema as $name => $value) {
            if (str_starts_with($name, 'x-')) {
                $extraAttributes[$name] = $value;
            }
        }

        if (array_key_exists('x-icon', $extraAttributes)) {
            $this->validateIcon($extraAttributes['x-icon']);
        }

        if (array_key_exists('x-color', $extraAttributes)) {
            $this->validateColor($extraAttributes['x-color']);
        }

        if ($extraAttributes !== []) {
            $this->extraAttributes[$type] = $extraAttributes;
        }
    }

    private function validateIcon(mixed $icon): void
    {
        if (!is_string($icon) || !preg_match('/^[a-zA-Z0-9_-]+$/D', $icon)) {
            throw new InvalidArgumentException('The JSON Schema [x-icon] must be a Blade icon name.');
        }
    }

    private function validateColor(mixed $color): void
    {
        $number = '[+-]?(?:\\d+(?:\\.\\d+)?|\\.\\d+)%?';
        $commaRgb = $number . '\\s*,\\s*' . $number . '\\s*,\\s*' . $number . '(?:\\s*,\\s*' . $number . ')?';
        $spaceRgb = $number . '\\s+' . $number . '\\s+' . $number . '(?:\\s*/\\s*' . $number . ')?';
        $pattern =
            '~^(?:[a-z][a-z0-9_-]*|\\#(?:[a-f0-9]{3,4}|[a-f0-9]{6}|[a-f0-9]{8})|rgba?\\(\\s*(?:'
            . $commaRgb
            . '|'
            . $spaceRgb
            . ')\\s*\\))$~iD';

        if (!is_string($color) || !preg_match($pattern, $color)) {
            throw new InvalidArgumentException(
                'The JSON Schema [x-color] must be a Filament color name, HEX or RGB/RGBA color.',
            );
        }
    }
}

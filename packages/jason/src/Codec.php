<?php

namespace PHPinnacle\Jason;

use InvalidArgumentException;
use stdClass;

/** @internal */
final class Codec
{
    public const array MAPS = ['properties', 'patternProperties', '$defs', 'definitions', 'dependentSchemas'];

    public const array LISTS = ['anyOf', 'oneOf', 'allOf', 'prefixItems'];

    public const array SCHEMAS = [
        'items',
        'additionalProperties',
        'additionalItems',
        'unevaluatedProperties',
        'unevaluatedItems',
        'contains',
        'not',
        'if',
        'then',
        'else',
        'propertyNames',
        'contentSchema',
    ];

    /**
     * @param array<string, mixed>|bool $schema
     * @return array<string, mixed>|bool
     */
    public static function copy(array|bool $schema): array|bool
    {
        return is_bool($schema) ? $schema : array_map(self::copyValue(...), $schema);
    }

    public static function copyValue(mixed $value): mixed
    {
        return match (true) {
            is_array($value) => array_map(self::copyValue(...), $value),
            $value instanceof stdClass => (object) array_map(self::copyValue(...), (array) $value),
            default => $value,
        };
    }

    /** @return array<string, mixed>|bool */
    public static function decode(mixed $schema): array|bool
    {
        if (is_bool($schema)) {
            return $schema;
        }
        if (!$schema instanceof stdClass) {
            throw new InvalidArgumentException('A JSON Schema must be an object or a boolean.');
        }
        /** @var array<string, mixed> $definition JSON object keywords, including unknown annotations. */
        $definition = get_object_vars($schema);
        foreach ($definition as $keyword => $value) {
            if ($keyword === 'type') {
                $types = is_array($value) ? $value : [$value];
                if (
                    $types === []
                    || !array_all(
                        $types,
                        fn ($type) => (
                            is_string($type)
                            && in_array(
                                $type,
                                ['object', 'array', 'string', 'integer', 'number', 'boolean', 'null'],
                                true,
                            )
                        ),
                    )
                ) {
                    throw new InvalidArgumentException('The schema type must name a JSON primitive type.');
                }
            }
            if (in_array($keyword, self::MAPS, true)) {
                if (!$value instanceof stdClass) {
                    throw new InvalidArgumentException('The schema keyword [' . $keyword . '] must be an object.');
                }
                $definition[$keyword] = array_map(self::decode(...), (array) $value);
            } elseif (in_array($keyword, self::LISTS, true)) {
                if (!is_array($value)) {
                    throw new InvalidArgumentException('The schema keyword [' . $keyword . '] must be an array.');
                }
                $definition[$keyword] = array_map(self::decode(...), $value);
            } elseif (in_array($keyword, self::SCHEMAS, true)) {
                $definition[$keyword] = self::decode($value);
            }
        }

        return $definition;
    }

    /** @param array<string, mixed>|bool $schema */
    public static function encode(array|bool $schema): stdClass|bool
    {
        if (is_bool($schema)) {
            return $schema;
        }
        foreach ($schema as $keyword => $value) {
            if (in_array($keyword, self::MAPS, true)) {
                /** @var array<string, array<string, mixed>|bool> $value */
                $schema[$keyword] = (object) array_map(self::encode(...), $value);
            } elseif (in_array($keyword, self::LISTS, true)) {
                /** @var list<array<string, mixed>|bool> $value */
                $schema[$keyword] = array_map(self::encode(...), $value);
            } elseif (in_array($keyword, self::SCHEMAS, true)) {
                /** @var array<string, mixed>|bool $value */
                $schema[$keyword] = self::encode($value);
            }
        }

        return (object) $schema;
    }
}

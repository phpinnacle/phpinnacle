<?php

namespace PHPinnacle\Porta\Enums;

use PHPinnacle\Porta\Models\Transform;
use PHPinnacle\Porta\Resources\Integrations\Schemas\TransformConfiguration;

/** @phpstan-type TransformConfig array{fields?: array<array-key, mixed>, dest?: string|null, type?: string|null, chars?: string|null, input?: string|null, output?: string|null, modify?: string|null, behavior?: string|null, locale?: string|null, future?: bool|null} */
enum TransformType: string
{
    case MAP = 'map';
    case DROP = 'drop';
    case MOVE = 'move';
    case CAST = 'cast';
    case TRIM = 'trim';
    case DATE = 'date';
    case RENAME = 'rename';
    case INSERT = 'insert';

    public static function resolve(string|self $value): self
    {
        return is_string($value) ? self::from($value) : $value;
    }

    /**
     * @return list<\Filament\Schemas\Components\Component>
     */
    public function form(): array
    {
        return new TransformConfiguration($this)->form();
    }

    /**
     * @param array<array-key, mixed> $data
     * @param TransformConfig $config
     * @return array<array-key, mixed>
     */
    public function transform(array $data, string $path, array $config): array
    {
        return new Transform($path, $this, $config)->apply($data);
    }

    /**
     * @param TransformConfig $config
     */
    public function preview(array $config): string
    {
        return new TransformConfiguration($this)->preview($config);
    }
}

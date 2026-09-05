<?php

namespace PHPinnacle\Porta\Models;

use PHPinnacle\Porta\Enums\TransformType;

/**
 * @phpstan-import-type TransformConfig from TransformType
 * @phpstan-type TransformData array{path?: string, type: string|TransformType, config?: TransformConfig}
 */
readonly class Transform
{
    /**
     * @param TransformConfig $config
     */
    public function __construct(
        private string $path,
        private TransformType $type,
        private array $config = [],
    ) {}

    /**
     * @param TransformData $data
     */
    public static function make(array $data): self
    {
        $path = $data['path'] ?? '$';

        if (!str_starts_with($path, '$')) {
            $path = '$.' . $path;
        }

        return new self($path, TransformType::resolve($data['type']), $data['config'] ?? []);
    }

    /**
     * @param array<array-key, mixed> $data
     * @return array<array-key, mixed>
     */
    public function apply(array $data): array
    {
        return $this->type->transform($data, $this->path, $this->config);
    }
}

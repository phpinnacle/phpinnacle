<?php

namespace PHPinnacle\Porta\Models;

use PHPinnacle\Porta\Enums\TransformType;

readonly class Transform
{
    public function __construct(
        private string $path,
        private TransformType $type,
        private array $config = [],
    ) {}

    public static function make(array $data): self
    {
        $path = $data['path'] ?? '$';

        if (!str_starts_with($path, '$')) {
            $path = '$.' . $path;
        }

        return new self($path, TransformType::resolve($data['type']), $data['config'] ?? []);
    }

    public function apply(array $data): array
    {
        return $this->type->transform($data, $this->path, $this->config);
    }
}

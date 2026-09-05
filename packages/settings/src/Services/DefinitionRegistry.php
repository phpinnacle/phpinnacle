<?php

namespace PHPinnacle\Settings\Services;

use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Collection;
use PHPinnacle\Settings\Definition;

#[Singleton]
class DefinitionRegistry
{
    /**
     * @var array<string, Definition>
     */
    private array $definitions = [];

    private ?string $default = null;

    public function register(Definition ...$definitions): void
    {
        foreach ($definitions as $definition) {
            $this->definitions[$definition->slug] = $definition;
        }

        uasort($this->definitions, fn (Definition $a, Definition $b) => $a->sort <=> $b->sort);

        $this->default ??= array_key_first($this->definitions);
    }

    public function default(): ?string
    {
        return $this->default;
    }

    /**
     * @return Collection<string, Definition>
     */
    public function all(): Collection
    {
        return collect($this->definitions);
    }

    public function get(string $group): ?Definition
    {
        return $this->definitions[$group] ?? null;
    }
}

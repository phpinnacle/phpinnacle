<?php

namespace PHPinnacle\Razor\Services;

use InvalidArgumentException;
use PHPinnacle\Razor\Models\Section;

class Registry
{
    /**
     * @var array<string, Section>
     */
    private array $sections = [];

    public function add(Section|string ...$sections): void
    {
        foreach ($sections as $section) {
            $section = is_string($section) ? Section::make($section) : $section;

            $this->sections[$section->key] = $section;
        }
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->sections);
    }

    public function get(string $key): Section
    {
        return $this->sections[$key] ?? throw new InvalidArgumentException('Unknown template section');
    }

    /**
     * @return array<string, Section>
     */
    public function all(): array
    {
        return $this->sections;
    }
}

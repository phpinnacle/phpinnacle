<?php

namespace PHPinnacle\Pinax;

class Mark
{
    private const string STATE_KEY = 'marks';

    public function __construct(
        public string $key,
        public string $label,
        public string $icon,
        public string $color,
        public bool $unique = false,
        public bool $default = false,
    ) {}

    public static function make(string $key): self
    {
        return new self(
            key: $key,
            label: str($key)->replace(['_', '-'], ' ')->apa(),
            icon: 'phosphor-star',
            color: 'primary',
        );
    }

    public function color(string $value): self
    {
        $this->color = $value;

        return $this;
    }

    public function default(bool $value = true): self
    {
        $this->default = $value;

        return $this;
    }

    public function exists(array $state, string $key = self::STATE_KEY): bool
    {
        return array_any($state, fn ($item) => in_array($this->key, $item[$key] ?? [], strict: true));
    }

    public function getColor(array $state, string $key = self::STATE_KEY): string
    {
        return in_array($this->key, $state[$key] ?? [], strict: true)
            ? $this->color
            : 'gray';
    }

    public function getIcon(array $state, string $key = self::STATE_KEY): string
    {
        return in_array($this->key, $state[$key] ?? [], strict: true)
            ? sprintf('%s-fill', $this->icon)
            : $this->icon;
    }

    public function icon(string $value): self
    {
        $this->icon = $value;

        return $this;
    }

    public function label(string $value): self
    {
        $this->label = $value;

        return $this;
    }

    public function toggle(string $index, array $state): array
    {
        $current = $state[$index]['marks'] ?? [];
        $marked = in_array($this->key, $current, strict: true);

        $state[$index]['marks'] = $marked
            ? array_diff($current, [$this->key])
            : array_merge($current, [$this->key]);

        if ($this->unique && !$marked) {
            foreach ($state as $key => $item) {
                if ($key === $index) {
                    continue;
                }

                $state[$key]['marks'] = array_diff($item['marks'] ?? [], [$this->key]);
            }
        }

        return $state;
    }

    public function unique(bool $value = true): self
    {
        $this->unique = $value;

        return $this;
    }
}

<?php

namespace PHPinnacle\Sequentia;

use DateTimeInterface;

final readonly class Sequence
{
    public function __construct(
        public string $pattern,
        public string $scope,
        public array $bucket,
        public DateTimeInterface $date,
        public ?string $tenant = null,
    ) {}

    public static function create(
        string $pattern,
        string $scope,
        array $bucket = [],
    ): self {
        return new self($pattern, $scope, $bucket, now());
    }

    public function forTenant(string|int|null $tenant): self
    {
        return new self(
            $this->pattern,
            $this->scope,
            $this->bucket,
            $this->date,
            $tenant !== null ? (string) $tenant : null,
        );
    }

    public function on(DateTimeInterface $date): self
    {
        return new self($this->pattern, $this->scope, $this->bucket, $date, $this->tenant);
    }

    public function get(array $context = []): string
    {
        return PatternFormatter::format($this->pattern, $this->prepare($context));
    }

    private function prepare(array $context): array
    {
        $context['D'] = $this->date->format('j');
        $context['DD'] = $this->date->format('d');
        $context['M'] = $this->date->format('n');
        $context['MM'] = $this->date->format('m');
        $context['Y'] = $this->date->format('y');
        $context['YY'] = $this->date->format('Y');
        $context['W'] = $this->date->format('W');

        $counts = SequenceWatcher::load($this->scope, $this->date, $this->tenant, $this->bucket);

        return array_change_key_case(array_replace($counts, $context));
    }
}

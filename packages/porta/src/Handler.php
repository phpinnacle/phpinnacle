<?php

namespace PHPinnacle\Porta;

use Closure;
use Filament\Support\Concerns\EvaluatesClosures;
use InvalidArgumentException;
use PHPinnacle\Porta\Contracts\Handler as Contract;

class Handler implements Contract
{
    use EvaluatesClosures;

    public function __construct(
        private string $type,
        private string $label,
        private string|Closure $invoker,
        private string|Closure $example = '',
    ) {
        if (is_string($this->invoker) && !class_exists($this->invoker)) {
            throw new InvalidArgumentException("Invoker must be a valid class name or callable, got: {$this->invoker}");
        }
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getExample(): ?string
    {
        return $this->evaluate($this->example);
    }

    /**
     * @param array<array-key, mixed> $payload
     */
    public function process(array $payload): void
    {
        $this->evaluate($this->invoker, [
            'payload' => $payload,
        ]);
    }
}

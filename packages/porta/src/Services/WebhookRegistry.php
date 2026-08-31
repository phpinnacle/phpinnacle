<?php

namespace PHPinnacle\Porta\Services;

use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Collection;
use PHPinnacle\Porta\Contracts\Handler;

#[Singleton]
class WebhookRegistry
{
    private array $handlers = [];

    public function all(): Collection
    {
        return collect($this->handlers);
    }

    public function get(string $type): ?Handler
    {
        return $this->handlers[$type] ?? null;
    }

    public function options(): array
    {
        return $this
            ->all()
            ->mapWithKeys(fn (Handler $handler) => [$handler->getType() => $handler->getLabel()])
            ->all();
    }

    public function register(Handler ...$handlers): void
    {
        foreach ($handlers as $handler) {
            $this->handlers[$handler->getType()] = $handler;
        }
    }
}

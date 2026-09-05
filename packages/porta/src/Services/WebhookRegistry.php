<?php

namespace PHPinnacle\Porta\Services;

use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Collection;
use PHPinnacle\Porta\Contracts\Handler;

#[Singleton]
class WebhookRegistry
{
    /**
     * @var array<string, Handler>
     */
    private array $handlers = [];

    public function register(Handler ...$handlers): void
    {
        foreach ($handlers as $handler) {
            $this->handlers[$handler->getType()] = $handler;
        }
    }

    public function get(string $type): ?Handler
    {
        return $this->handlers[$type] ?? null;
    }

    /**
     * @return Collection<string, Handler>
     */
    public function all(): Collection
    {
        return collect($this->handlers);
    }

    /**
     * @return array<string, string|null>
     */
    public function options(): array
    {
        return $this
            ->all()
            ->mapWithKeys(fn (Handler $handler) => [$handler->getType() => $handler->getLabel()])
            ->all();
    }
}

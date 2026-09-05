<?php

namespace PHPinnacle\Porta;

use Filament\Contracts\Plugin;
use Filament\Panel;
use PHPinnacle\Porta\Services\WebhookRegistry;

class PortaPlugin implements Plugin
{
    public function __construct(
        private WebhookRegistry $registry,
    ) {}

    public static function get(): static
    {
        // @mago-expect lint:inline-variable-return
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public function boot(Panel $panel): void {}

    public function getId(): string
    {
        return 'phpinnacle/porta';
    }

    public function handle(Handler ...$handlers): self
    {
        $this->registry->register(...$handlers);

        return $this;
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            Resources\Integrations\IntegrationResource::class,
        ]);
    }
}

<?php

namespace PHPinnacle\Pinax;

use Filament\Contracts\Plugin;
use Filament\Panel;

class PinaxPlugin implements Plugin
{
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
        return 'phpinnacle/pinax';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            Resources\Media\MediaResource::class,
        ]);
    }
}

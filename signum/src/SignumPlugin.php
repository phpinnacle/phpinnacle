<?php

namespace PHPinnacle\Signum;

use Filament\Contracts\Plugin;
use Filament\Panel;

class SignumPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    public function getId(): string
    {
        return 'phpinnacle/signum';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            Resources\Alerts\AlertResource::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
    }
}

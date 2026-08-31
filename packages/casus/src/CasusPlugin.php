<?php

namespace PHPinnacle\Casus;

use Filament\Contracts\Plugin;
use Filament\Panel;

class CasusPlugin implements Plugin
{
    public static function get(): static
    {
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
        return 'phpinnacle/casus';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            Resources\Exceptions\ExceptionResource::class,
        ]);
    }
}

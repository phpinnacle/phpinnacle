<?php

namespace PHPinnacle\Chronica;

use Filament\Contracts\Plugin;
use Filament\Panel;

class ChronicaPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static */
        return filament(app(static::class)->getId());
    }

    public function getId(): string
    {
        return 'phpinnacle/chronica';
    }

    public function register(Panel $panel): void {}

    public function boot(Panel $panel): void {}
}

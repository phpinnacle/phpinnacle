<?php

namespace PHPinnacle\Settings;

use Closure;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use PHPinnacle\Settings\Pages\SettingsPage;
use PHPinnacle\Settings\Services\DefinitionRegistry;

class SettingsPlugin implements Plugin
{
    use EvaluatesClosures;

    /**
     * @var array<array-key, Definition|Closure|string>
     */
    private array $definitions = [];

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        // @mago-expect lint:inline-variable-return
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    public function getId(): string
    {
        return 'phpinnacle/settings';
    }

    public function definitions(Definition|Closure|string ...$definitions): static
    {
        $this->definitions = [
            ...$this->definitions,
            ...$definitions,
        ];

        return $this;
    }

    public function register(Panel $panel): void
    {
        $panel->pages([
            SettingsPage::class,
        ]);
    }

    public function boot(Panel $panel): void {}

    public function load(DefinitionRegistry $registry): void
    {
        foreach ($this->definitions as $definition) {
            $definitions = array_map(
                fn (Definition|string $def) => is_string($def) ? Definition::make($def) : $def,
                (array) $this->evaluate($definition),
            );

            $registry->register(...array_values($definitions));
        }
    }
}

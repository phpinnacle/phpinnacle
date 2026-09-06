<?php

namespace PHPinnacle\Settings;

use Closure;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Illuminate\Foundation\Application;
use PHPinnacle\Settings\Pages\SettingsPage;
use PHPinnacle\Settings\Services\DefinitionRegistry;
use PHPinnacle\Settings\Services\SettingsStorage;

class SettingsPlugin implements Plugin
{
    use EvaluatesClosures;

    /**
     * @var array<array-key, Definition|class-string|array<Definition|class-string>|Closure(): (Definition|class-string|array<Definition|class-string>)>
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

    /**
     * @param Definition|class-string|Closure(): (Definition|class-string|array<Definition|class-string>) ...$definitions
     */
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
        $this->definitions = $this->resolveDefinitionGroups();

        foreach ($this->definitions as $definitions) {
            foreach ($definitions as $definition) {
                $class = $definition->class;

                app()->singleton($class, function (Application $app) use ($class) {
                    $settings = $app->build($class);
                    $app->make(SettingsStorage::class)->fill($settings);

                    return $settings;
                });
            }
        }

        $panel->pages([
            SettingsPage::class,
        ]);
    }

    public function boot(Panel $panel): void {}

    public function load(DefinitionRegistry $registry): void
    {
        foreach ($this->resolveDefinitionGroups() as $definitions) {
            $registry->register(...$definitions);
        }
    }

    /**
     * @return list<list<Definition>>
     */
    private function resolveDefinitionGroups(): array
    {
        $resolved = [];

        foreach ($this->definitions as $definition) {
            $definitions = $this->evaluate($definition);
            $group = [];

            foreach (is_array($definitions) ? $definitions : [$definitions] as $item) {
                $group[] = is_string($item) ? Definition::make($item) : $item;
            }

            $resolved[] = $group;
        }

        return $resolved;
    }
}

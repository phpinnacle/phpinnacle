<?php

namespace PHPinnacle\Razor;

use Closure;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use PHPinnacle\Razor\Models\Section;
use PHPinnacle\Razor\Services\Registry;

class RazorPlugin implements Plugin
{
    use EvaluatesClosures;

    private array $sections = [];

    public function __construct(
        private Registry $registry,
    ) {}

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

    public function sections(Section|Closure ...$sections): static
    {
        $this->sections = [
            ...$this->sections,
            ...$sections,
        ];

        return $this;
    }

    public function getId(): string
    {
        return 'phpinnacle/razor';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            Resources\Templates\TemplateResource::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        foreach ($this->sections as $section) {
            $section = $this->evaluate($section);

            switch (true) {
                case is_string($section):
                case $section instanceof Section:
                    $this->registry->add($section);

                    break;
                case is_array($section):
                    $this->registry->add(...array_values($section));

                    break;
                default:
                    continue 2;
            }
        }
    }
}

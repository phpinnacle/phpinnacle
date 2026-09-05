<?php

namespace PHPinnacle\Recens;

use BackedEnum;
use Closure;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Filament\View\PanelsRenderHook;
use PHPinnacle\Recens\Models\Recent;

class RecensPlugin implements Plugin
{
    use EvaluatesClosures;

    private string|array|Closure $color = 'gray';

    private string|BackedEnum|Closure $icon = 'heroicon-o-clock';

    private int|Closure|null $limit = null;

    private array $scopes = [];

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

    public function color(string|array|Closure $color): self
    {
        $this->color = $color;

        return $this;
    }

    public function getId(): string
    {
        return 'phpinnacle/recens';
    }

    public function icon(string|BackedEnum|Closure $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function limit(int|Closure $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    public function load(Recorder $recorder): void
    {
        foreach ($this->scopes as $scope => $callback) {
            $recorder->register($scope, $callback);
        }
    }

    public function register(Panel $panel): void
    {
        $panel->renderHook(
            name: PanelsRenderHook::USER_MENU_BEFORE,
            hook: fn () => view('phpinnacle-recens::entries', [
                'entries' => Recent::list($this->evaluate($this->limit)),
                'color' => $this->evaluate($this->color),
                'icon' => $this->evaluate($this->icon),
            ]),
        );

        $panel->renderHook(
            name: PanelsRenderHook::PAGE_START,
            hook: fn () => view('phpinnacle-recens::hook', [
                'recorder' => app(Recorder::class),
            ]),
            scopes: array_keys($this->scopes),
        );
    }

    public function scopes(array $scopes): self
    {
        $this->scopes = array_is_list($scopes) ? array_fill_keys($scopes, null) : $scopes;

        return $this;
    }
}

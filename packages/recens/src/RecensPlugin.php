<?php

namespace PHPinnacle\Recens;

use BackedEnum;
use Closure;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;

class RecensPlugin implements Plugin
{
    use EvaluatesClosures;

    /**
     * @var string|array<string>|Closure
     */
    private string|array|Closure $color = 'gray';

    private string|BackedEnum|Closure $icon = 'heroicon-o-clock';

    private int|Closure|null $limit = null;

    private ?Recorder $recorder = null;

    /**
     * @var array<string, Closure|null>
     */
    private array $scopes = [];

    public static function make(): static
    {
        return app()->get(static::class);
    }

    public static function get(): static
    {
        // @mago-expect lint:inline-variable-return
        /** @var static $plugin */
        $plugin = filament(static::make()->getId());

        return $plugin;
    }

    public function getId(): string
    {
        return 'phpinnacle/recens';
    }

    /**
     * @param string|array<string>|Closure $color
     */
    public function color(string|array|Closure $color): self
    {
        $this->color = $color;

        return $this;
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

    /**
     * @param list<string>|array<string, Closure|null> $scopes
     */
    public function scopes(array $scopes): self
    {
        if (array_is_list($scopes)) {
            /** @var list<string> $scopes */
            $this->scopes = array_fill_keys($scopes, null);
        } else {
            /** @var array<string, Closure|null> $scopes */
            $this->scopes = $scopes;
        }

        return $this;
    }

    public function register(Panel $panel): void
    {
        $panel->renderHook(
            name: PanelsRenderHook::USER_MENU_BEFORE,
            hook: fn () => Blade::render('@livewire("phpinnacle-recens.entries")'),
        );

        $panel->renderHook(
            name: PanelsRenderHook::PAGE_START,
            hook: fn () => view('phpinnacle-recens::hook', [
                'recorder' => app(Recorder::class),
            ]),
            scopes: array_keys($this->scopes),
        );
    }

    /** @return string|array<string> */
    public function getColor(): string|array
    {
        // @mago-expect lint:inline-variable-return
        /** @var string|array<string> $color */
        $color = $this->evaluate($this->color);

        return $color;
    }

    public function getIcon(): string|BackedEnum
    {
        // @mago-expect lint:inline-variable-return
        /** @var string|BackedEnum $icon */
        $icon = $this->evaluate($this->icon);

        return $icon;
    }

    public function getLimit(): ?int
    {
        // @mago-expect lint:inline-variable-return
        /** @var ?int $limit */
        $limit = $this->evaluate($this->limit);

        return $limit;
    }

    public function getRecorder(): Recorder
    {
        $recorder = $this->recorder;

        if ($recorder === null) {
            $recorder = new Recorder;
            $this->recorder = $recorder;
            $this->load($recorder);
        }

        return $recorder;
    }

    public function boot(Panel $panel): void {}

    public function load(Recorder $recorder): void
    {
        foreach ($this->scopes as $scope => $callback) {
            $recorder->register($scope, $callback);
        }
    }
}

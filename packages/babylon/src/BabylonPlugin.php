<?php

namespace PHPinnacle\Babylon;

use Filament\Contracts\Plugin;
use Filament\Forms\Components\Select;
use Filament\Panel;
use Filament\Schemas\Components\Section;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use PHPinnacle\Intl\Locales;

class BabylonPlugin implements Plugin
{
    public const string ID = 'phpinnacle/babylon';

    private array $locales = [];

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

    public static function profile(): Section
    {
        return Section::make()
            ->heading(__('phpinnacle-babylon::profile.section.heading'))
            ->description(__('phpinnacle-babylon::profile.section.description'))
            ->statePath('babylon')
            ->aside()
            ->schema([
                Select::make('locale')
                    ->label(__('phpinnacle-babylon::profile.fields.locale.label'))
                    ->selectablePlaceholder(false)
                    ->options(fn () => array_column(BabylonPlugin::get()->getLocales(), 'name', 'code'))
                    ->default(App::getLocale()),
            ]);
    }

    public function boot(Panel $panel): void {}

    public function getId(): string
    {
        return self::ID;
    }

    public function locales(string ...$locales): self
    {
        $this->locales = array_unique(array_merge($this->locales, $locales));

        return $this;
    }

    public function register(Panel $panel): void
    {
        $panel->middleware(
            [
                Http\Middleware\SetLocale::class,
            ],
            isPersistent: true,
        );

        $panel->renderHook(
            name: PanelsRenderHook::USER_MENU_BEFORE,
            hook: fn () => view('phpinnacle-babylon::locale-switcher', [
                'locales' => $this->getLocales(),
                'current' => App::getLocale(),
            ]),
        );
    }

    private function getLocales(): array
    {
        return array_map(fn (string $locale) => [
            'code' => $locale,
            'name' => Str::title(Locales::name($locale, $locale)),
        ], $this->locales);
    }
}

<?php

namespace PHPinnacle\Babylon;

use Closure;
use Filament\Contracts\Plugin;
use Filament\Forms\Components\Select;
use Filament\Panel;
use Filament\Schemas\Components\Section;
use Filament\Support\Concerns\EvaluatesClosures;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use Locale;

class BabylonPlugin implements Plugin
{
    use EvaluatesClosures;

    public const string ID = 'phpinnacle/babylon';

    public const string PREFERENCE_GROUP = 'babylon';

    public const string PREFERENCE_KEY = 'locale';

    private string $panelId;

    /**
     * @var array<int, string>
     */
    private array $locales = [];

    private ?Closure $userLocaleResolver = null;

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

    public static function profile(): Section
    {
        $plugin = static::get();

        return Section::make()
            ->heading(__('phpinnacle-babylon::profile.section.heading'))
            ->description(__('phpinnacle-babylon::profile.section.description'))
            ->statePath($plugin->getPreferenceGroup())
            ->aside()
            ->schema([
                Select::make(self::PREFERENCE_KEY)
                    ->label(__('phpinnacle-babylon::profile.fields.locale.label'))
                    ->selectablePlaceholder(false)
                    ->options($plugin->getLocales())
                    ->default(App::getLocale()),
            ]);
    }

    public function boot(Panel $panel): void {}

    public function getId(): string
    {
        return self::ID;
    }

    /**
     * @return array<string, string>
     */
    public function getLocales(): array
    {
        return array_combine(
            $this->locales,
            array_map(
                fn (string $locale) => Str::title((string) Locale::getDisplayName($locale, $locale)),
                $this->locales,
            ),
        );
    }

    public function getPreferenceGroup(): string
    {
        return self::PREFERENCE_GROUP . '-' . $this->panelId;
    }

    public function getSessionKey(): string
    {
        return $this->getPreferenceGroup() . '.' . self::PREFERENCE_KEY;
    }

    public function getUserLocale(Authenticatable $user): ?string
    {
        // @mago-expect lint:inline-variable-return
        /** @var ?string $locale */
        $locale = $this->evaluate(
            $this->userLocaleResolver,
            namedInjections: [
                'user' => $user,
                'plugin' => $this,
            ],
            typedInjections: [
                Authenticatable::class => $user,
            ],
        );

        return $locale;
    }

    public function locales(string ...$locales): self
    {
        $this->locales = array_unique(array_merge($this->locales, $locales));

        return $this;
    }

    public function register(Panel $panel): void
    {
        $this->panelId = $panel->getId();

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
                'panel' => $panel->getId(),
            ]),
        );
    }

    public function userLocaleUsing(Closure $callback): self
    {
        $this->userLocaleResolver = $callback;

        return $this;
    }
}

<?php

namespace PHPinnacle\Cerber;

use Closure;
use Filament\Actions\Action;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Auth\MultiFactor\Email\EmailAuthentication;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Concerns\EvaluatesClosures;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Lab404\Impersonate\Services\ImpersonateManager;
use Livewire\Component;
use PHPinnacle\Cerber\Models\Provider;
use PHPinnacle\Cerber\Models\Tenant;
use PHPinnacle\Cerber\Models\User;
use PHPinnacle\Cerber\Services\ProviderRegistry;

class CerberPlugin implements Plugin
{
    use EvaluatesClosures;

    public const string ID = 'phpinnacle/cerber';

    private bool $tenancy = false;

    private array $authProviders = [];

    private array $developers = [];

    private array $disabled = [];

    private ?Closure $modifyProfileForm = null;

    public function __construct(
        private readonly ProviderRegistry $providers,
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

    public function getId(): string
    {
        return self::ID;
    }

    public function tenancy(bool $value = true): static
    {
        $this->tenancy = $value;

        return $this;
    }

    public function authProviders(AuthProvider|Closure|string ...$providers): static
    {
        $this->authProviders = [
            ...$this->authProviders,
            ...$providers,
        ];

        return $this;
    }

    public function developers(array $developers): static
    {
        $this->developers = array_is_list($developers) ? array_combine($developers, $developers) : $developers;

        return $this;
    }

    public function withoutProviders(): static
    {
        $this->disabled[] = Resources\Providers\ProviderResource::class;

        return $this;
    }

    public function withoutRoles(): static
    {
        $this->disabled[] = Resources\Roles\RoleResource::class;

        return $this;
    }

    public function withoutUsers(): static
    {
        $this->disabled[] = Resources\Users\UserResource::class;

        return $this;
    }

    public function withoutResources(): static
    {
        $this->disabled = [
            Resources\Providers\ProviderResource::class,
            Resources\Roles\RoleResource::class,
            Resources\Users\UserResource::class,
        ];

        return $this;
    }

    public function modifyProfileForm(Closure $callback): static
    {
        $this->modifyProfileForm = $callback;

        return $this;
    }

    public function scopes(string ...$models): static
    {
        Cerberus::registerToScope(...$models);

        return $this;
    }

    public function guards(string $group, array $permissions): static
    {
        if ($permissions === []) {
            return $this;
        }

        foreach ($permissions as $section => $items) {
            Cerberus::guard($group, $section, (array) $items);
        }

        return $this;
    }

    public function auth(string $credentials, Panel $panel, ?Model $tenant): bool
    {
        if (app()->isProduction()) {
            return false;
        }

        if (!array_key_exists($credentials, $this->developers)) {
            return false;
        }

        if (!($user = User::find($credentials))) {
            return false;
        }

        if (!$user->canAccessPanel($panel)) {
            return false;
        }

        if ($tenant !== null && !$user->canAccessTenant($tenant)) {
            return false;
        }

        $auth = $panel->auth();

        if ($auth->check()) {
            $auth->logout();
        }

        $panel->auth()->login($user);

        session()->regenerate();

        return true;
    }

    public function doModifyProfileForm(Schema $schema, Pages\EditProfile $page): Schema
    {
        return (
            $this->modifyProfileForm !== null
                ? $this->evaluate(
                    $this->modifyProfileForm,
                    [
                        'schema' => $schema,
                        'page' => $page,
                    ],
                    [
                        Schema::class => $schema,
                        Pages\EditProfile::class => $page,
                    ],
                )
                : $schema->components([
                    $page->getProfileContentComponent(),
                    $page->getPasswordContentComponent(),
                    $page->getOAuthAccountsComponent(),
                ])
        );
    }

    public function register(Panel $panel): void
    {
        /** @var array<class-string> $resources */
        $resources = array_diff(
            [
                Resources\Providers\ProviderResource::class,
                Resources\Roles\RoleResource::class,
                Resources\Users\UserResource::class,
            ],
            $this->disabled,
        );

        $panel
            ->login()
            ->passwordReset()
            ->emailVerification()
            ->resources($resources)
            ->pages([
                Pages\EditProfile::class,
            ])
            ->userMenuItems([
                'profile' => fn () => Action::make('profile')
                    ->label(__('phpinnacle-cerber::actions.profile.label'))
                    ->icon('phosphor-user-circle')
                    ->url(Pages\EditProfile::getUrl()),
                'logout' => fn (ImpersonateManager $manager) => Action::make('logout')
                    ->label(__('phpinnacle-cerber::actions.logout.label'))
                    ->icon($manager->isImpersonating() ? 'phosphor-user-switch' : 'phosphor-sign-out')
                    ->action(
                        $manager->isImpersonating()
                            ? function (Component $livewire) use ($manager, $panel) {
                                $manager->leave();

                                $livewire->redirect(session()->pull('impersonate.back_to', $panel->getUrl()));
                            } : null,
                    )
                    ->url(!$manager->isImpersonating() ? $panel->getLogoutUrl() : null)
                    ->postToUrl(),
            ])
            ->multiFactorAuthentication([
                AppAuthentication::make()->recoverable(),
                EmailAuthentication::make(),
            ]);

        if ($this->developers !== [] && !app()->isProduction()) {
            $panel->renderHook(PanelsRenderHook::AUTH_LOGIN_FORM_AFTER, fn () => $this->developersHook($panel));
            $panel->routes(fn () => [
                Route::post('/developer-auth', Http\Controllers\LoginController::class)
                    ->name('cerber-developer-auth')
                    ->domain($panel->getTenantDomain()),
            ]);
        }

        if ($this->authProviders !== []) {
            $panel->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
                fn (ProviderRegistry $registry) => $this->providersHook($panel, $registry),
            );
            $panel->routes(fn () => [
                Route::get('/auth/{provider}/redirect', Http\Controllers\OAuth\RedirectController::class)
                    ->name('auth.redirect'),
                Route::get('/auth/{provider}/callback', Http\Controllers\OAuth\CallbackController::class)
                    ->name('auth.callback'),
                Route::get('/auth/{provider}/test', Http\Controllers\OAuth\TestController::class)
                    ->name('auth.test'),
            ]);
            $panel->authenticatedRoutes(fn () => [
                Route::get('/auth/{provider}/link', Http\Controllers\OAuth\LinkController::class)
                    ->name('auth.link'),
            ]);
        }

        if ($this->tenancy) {
            $panel
                ->tenant(Tenant::class, slugAttribute: 'domain')
                ->tenantDomain(sprintf('{tenant:domain}.%s', parse_url(config('app.url'), PHP_URL_HOST)));
        }
    }

    public function boot(Panel $panel): void
    {
        if ($this->tenancy) {
            Resource::scopeToTenant(false);
            Cerberus::registerScoped();
        }

        foreach ($this->authProviders as $providers) {
            $providers = (array) $this->evaluate($providers);

            foreach ($providers as $provider) {
                $provider = is_string($provider) ? AuthProvider::make($provider) : $provider;

                $this->providers->add($provider);
            }
        }
    }

    protected function providersHook(Panel $panel, ProviderRegistry $registry): string
    {
        $providers = Provider::valid()->map(function (Provider $provider) use ($panel, $registry) {
            $type = $registry->get($provider->type);

            return (
                $type !== null
                    ? (object) [
                        'label' => $provider->getLabel(),
                        'color' => $type->getColor(),
                        'icon' => $type->getIcon(),
                        'url' => $provider->redirectUrl($panel),
                    ]
                    : null
            );
        })->filter();

        return $providers->isNotEmpty()
            ? view('phpinnacle-cerber::login.oauth', ['providers' => $providers])->render()
            : '';
    }

    private function developersHook(Panel $panel): View
    {
        return view('phpinnacle-cerber::login.developers', [
            'users' => $this->developers,
            'panel' => $panel->getId(),
            'route' => $panel->route('cerber-developer-auth', [
                'tenant' => $this->tenancy ? Tenant::resolve($panel)?->domain : null,
            ]),
        ]);
    }
}

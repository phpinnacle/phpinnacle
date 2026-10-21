<?php

namespace PHPinnacle\Cerber;

use BladeUI\Icons\Factory;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Lab404\Impersonate\Events\LeaveImpersonation;
use Lab404\Impersonate\Events\TakeImpersonation;
use Laravel\Sanctum\Sanctum;
use PHPinnacle\Cerber\Console\SyncPermissions;
use PHPinnacle\Cerber\Http\Middleware\AuthenticateSocialAccountContext;
use PHPinnacle\Cerber\Models\AccessToken;
use PHPinnacle\Cerber\Models\User;
use PHPinnacle\Cerber\Services\ImpersonationSession;
use PHPinnacle\Cerber\Services\SocialAccountContextRepository;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Yandex\Provider;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class CerberServiceProvider extends PackageServiceProvider
{
    public static string $name = 'phpinnacle-cerber';

    public function packageRegistered(): void
    {
        $this->callAfterResolving(Factory::class, function (Factory $factory) {
            $factory->add(self::$name, [
                'prefix' => 'cerber',
                'path' => __DIR__ . '/../resources/svg',
            ]);
        });
    }

    public function packageBooted(): void
    {
        Route::aliasMiddleware('cerber.context', AuthenticateSocialAccountContext::class);
        Route::bind(
            'socialAccountContext',
            fn (string $name) => app(SocialAccountContextRepository::class)->all()[$name] ?? abort(404),
        );

        Sanctum::usePersonalAccessTokenModel(AccessToken::class);

        Gate::after(fn (User $user, string $ability) => $user->able($ability, Filament::getTenant()));

        Event::listen(TakeImpersonation::class, [ImpersonationSession::class, 'clearAuthHashes']);
        Event::listen(LeaveImpersonation::class, [ImpersonationSession::class, 'clearAuthHashes']);
        Event::listen(SocialiteWasCalled::class, $this->socioliteCalled(...));
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->discoversMigrations()
            ->hasTranslations()
            ->hasConfigFile()
            ->hasViews()
            ->hasRoutes(['api'])
            ->hasCommands(
                SyncPermissions::class,
            )
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations();
            });
    }

    private function socioliteCalled(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('yandex', Provider::class);
    }
}

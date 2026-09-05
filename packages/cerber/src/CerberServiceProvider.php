<?php

namespace PHPinnacle\Cerber;

use BladeUI\Icons\Factory;
use Filament\Exceptions\NoDefaultPanelSetException;
use Filament\Facades\Filament;
use Filament\PanelRegistry;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Lab404\Impersonate\Events\LeaveImpersonation;
use Lab404\Impersonate\Events\TakeImpersonation;
use Laravel\Sanctum\Sanctum;
use PHPinnacle\Cerber\Console\SyncPermissions;
use PHPinnacle\Cerber\Models\AccessToken;
use PHPinnacle\Cerber\Models\User;
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
        Sanctum::usePersonalAccessTokenModel(AccessToken::class);

        Gate::after(fn (User $user, string $ability) => $user->able($ability, Filament::getTenant()));

        Event::listen(TakeImpersonation::class, $this->clearAuthHashes(...));
        Event::listen(LeaveImpersonation::class, $this->clearAuthHashes(...));
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

    /**
     * A missing default Filament panel has no panel-specific authentication hash to clear.
     *
     * @mago-expect lint:no-empty-catch-clause
     */
    private function clearAuthHashes(): void
    {
        $hashes = [
            'password_hash_sanctum',
            'password_hash_' . auth()->getDefaultDriver(),
        ];

        $guard = session('impersonate.guard');

        if ($guard) {
            $hashes[] = 'password_hash_' . $guard;
        }

        try {
            /** @throws NoDefaultPanelSetException */
            $hashes[] = 'password_hash_' . Filament::getCurrentOrDefaultPanel()->getAuthGuard();
        } catch (NoDefaultPanelSetException) {
        }

        $backToPanelId = session()->get('impersonate.back_to_panel');

        if ($backToPanelId) {
            $panel = app(PanelRegistry::class)->get($backToPanelId);

            if ($panel !== null) {
                $hashes[] = 'password_hash_' . $panel->getAuthGuard();
            }
        }

        session()->forget(array_unique($hashes));
    }

    private function socioliteCalled(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('yandex', Provider::class);
    }
}

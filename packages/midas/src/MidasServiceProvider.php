<?php

namespace PHPinnacle\Midas;

use PHPinnacle\Midas\Contracts\PaymentProvider;
use PHPinnacle\Midas\Services\ProviderRegistry;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class MidasServiceProvider extends PackageServiceProvider
{
    public static string $name = 'phpinnacle-midas';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->discoversMigrations()
            ->hasTranslations()
            ->hasViews()
            ->hasConfigFile()
            ->hasRoutes('api')
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->askToStarRepoOnGitHub('phpinnacle/midas');
            });
    }

    public function packageRegistered(): void
    {
        $this->callAfterResolving(ProviderRegistry::class, function (ProviderRegistry $registry) {
            /** @var list<class-string<PaymentProvider>> $providers */
            $providers = config('phpinnacle-midas.providers');

            foreach ($providers as $provider) {
                $registry->register($this->app->make($provider));
            }
        });
    }
}

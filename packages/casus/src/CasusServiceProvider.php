<?php

namespace PHPinnacle\Casus;

use Illuminate\Support\Facades\RateLimiter;
use PHPinnacle\Casus\Services\FrontendRateLimiter;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class CasusServiceProvider extends PackageServiceProvider
{
    public static string $name = 'phpinnacle-casus';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->discoversMigrations()
            ->hasTranslations()
            ->hasConfigFile()
            ->hasRoutes('web')
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->askToStarRepoOnGitHub('phpinnacle/casus');
            });
    }

    public function packageBooted(): void
    {
        RateLimiter::for('phpinnacle-casus.frontend', app(FrontendRateLimiter::class)->limits(...));
    }
}

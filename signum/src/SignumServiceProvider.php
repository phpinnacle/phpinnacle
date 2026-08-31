<?php

namespace PHPinnacle\Signum;

use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class SignumServiceProvider extends PackageServiceProvider
{
    public static string $name = 'phpinnacle-signum';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->discoversMigrations()
            ->hasTranslations()
            ->hasConfigFile()
            ->hasViews()
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->askToStarRepoOnGitHub('phpinnacle/signum');
            });
    }
}

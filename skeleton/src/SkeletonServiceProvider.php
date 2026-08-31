<?php

namespace PHPinnacle\Skeleton;

use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class SkeletonServiceProvider extends PackageServiceProvider
{
    public static string $name = 'phpinnacle-skeleton';

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
                    ->askToStarRepoOnGitHub('phpinnacle/skeleton');
            });
    }
}

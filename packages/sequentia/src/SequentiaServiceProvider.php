<?php

namespace PHPinnacle\Sequentia;

use PHPinnacle\Sequentia\Console\BuildSequences;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class SequentiaServiceProvider extends PackageServiceProvider
{
    public static string $name = 'phpinnacle-sequentia';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->discoversMigrations()
            ->hasConfigFile()
            ->hasCommand(BuildSequences::class)
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->askToStarRepoOnGitHub('phpinnacle/sequentia');
            });
    }
}

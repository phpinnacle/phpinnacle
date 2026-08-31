<?php

namespace PHPinnacle\Babylon;

use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class BabylonServiceProvider extends PackageServiceProvider
{
    public static string $name = 'phpinnacle-babylon';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasTranslations()
            ->hasConfigFile()
            ->hasViews()
            ->hasRoutes('web')
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->askToStarRepoOnGitHub('phpinnacle/babylon');
            });
    }
}

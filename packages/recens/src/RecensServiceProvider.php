<?php

namespace PHPinnacle\Recens;

use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class RecensServiceProvider extends PackageServiceProvider
{
    public static string $name = 'phpinnacle-recens';

    public function packageRegistered(): void
    {
        $this->callAfterResolving(Recorder::class, function (Recorder $recorder) {
            RecensPlugin::get()->load($recorder);
        });
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->discoversMigrations()
            ->hasTranslations()
            ->hasConfigFile()
            ->hasViews()
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->askToStarRepoOnGitHub('phpinnacle/recens');
            });
    }
}

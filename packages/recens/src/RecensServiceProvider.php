<?php

namespace PHPinnacle\Recens;

use Livewire\Livewire;
use PHPinnacle\Recens\Livewire\Entries;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class RecensServiceProvider extends PackageServiceProvider
{
    public static string $name = 'phpinnacle-recens';

    public function packageRegistered(): void
    {
        $this->app->bind(Recorder::class, fn () => RecensPlugin::get()->getRecorder());
    }

    public function packageBooted(): void
    {
        Livewire::component('phpinnacle-recens.entries', Entries::class);
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasMigrations(['create_recens_tables'])
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

<?php

namespace PHPinnacle\Comments;

use Livewire\Livewire;
use PHPinnacle\Comments\Livewire\Comments;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class CommentsServiceProvider extends PackageServiceProvider
{
    public static string $name = 'phpinnacle-comments';

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
                    ->askToStarRepoOnGitHub('phpinnacle/comments');
            });
    }

    public function packageBooted(): void
    {
        Livewire::component('comments', Comments::class);
    }
}

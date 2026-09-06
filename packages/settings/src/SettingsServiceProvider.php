<?php

namespace PHPinnacle\Settings;

use Illuminate\Database\Eloquent\Model;
use PHPinnacle\Settings\Models\Preference;
use PHPinnacle\Settings\Services\DefinitionRegistry;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class SettingsServiceProvider extends PackageServiceProvider
{
    public static string $name = 'phpinnacle-settings';

    public function packageRegistered(): void
    {
        $this->callAfterResolving(DefinitionRegistry::class, function (DefinitionRegistry $registry) {
            SettingsPlugin::get()->load($registry);
        });
    }

    public function packageBooted(): void
    {
        /** @var class-string<Model> $user */
        $user = config('phpinnacle-settings.user.model', '\\App\\Models\\User');

        if (class_exists($user) && is_subclass_of($user, Model::class)) {
            $user::resolveRelationUsing('preferences', fn (Model $record) => $record->hasMany(Preference::class));
        }
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
                    ->askToStarRepoOnGitHub('phpinnacle/settings');
            });
    }
}

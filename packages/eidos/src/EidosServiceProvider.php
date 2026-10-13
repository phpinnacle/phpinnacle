<?php

namespace PHPinnacle\Eidos;

use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Contracts\Container\Container;
use PHPinnacle\Eidos\Contracts\ProvidesEloquentModels;
use PHPinnacle\Eidos\Services\ModelRegistry;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class EidosServiceProvider extends PackageServiceProvider
{
    public static string $name = 'phpinnacle-eidos';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasTranslations()
            ->hasViews()
            ->hasConfigFile()
            ->hasCommand(Commands\WarmEloquentSchemas::class)
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->askToStarRepoOnGitHub('phpinnacle/eidos');
            });
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(ModelRegistry::class, function (Container $container) {
            /** @var list<class-string<ProvidesEloquentModels>> $sourceClasses */
            $sourceClasses = config('phpinnacle-eidos.model_sources');
            $sources = [];

            foreach ($sourceClasses as $sourceClass) {
                $sources[$sourceClass] = $container->make($sourceClass);
            }

            return new ModelRegistry($sources);
        });
    }

    public function packageBooted(): void
    {
        FilamentAsset::register([
            AlpineComponent::make('json-schema-picker', __DIR__ . '/../resources/js/json-schema-picker.js'),
            AlpineComponent::make('json-schema-tools', __DIR__ . '/../resources/js/json-schema-tools.js'),
            Js::make('components/json-schema-definition', __DIR__ . '/../resources/js/json-schema-definition.js')
                ->module()
                ->loadedOnRequest(),
            Js::make('components/json-schema-presentation', __DIR__ . '/../resources/js/json-schema-presentation.js')
                ->module()
                ->loadedOnRequest(),
            Css::make('json-schema-picker', __DIR__ . '/../resources/css/json-schema-picker.css'),
        ], 'phpinnacle/eidos');
    }
}

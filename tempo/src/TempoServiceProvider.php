<?php

namespace PHPinnacle\Tempo;

use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class TempoServiceProvider extends PackageServiceProvider
{
    public static string $name = 'phpinnacle-tempo';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasConfigFile()
            ->hasTranslations()
            ->hasViews();
    }

    public function packageBooted(): void
    {
        FilamentAsset::register(
            assets: [
                AlpineComponent::make('phpicker', __DIR__ . '/../resources/dist/phpicker.js'),
                AlpineComponent::make('tempo-calendar', __DIR__ . '/../resources/dist/calendar.js'),
                Css::make('phpicker', __DIR__ . '/../resources/dist/phpicker.css'),
                Css::make('tempo-calendar', __DIR__ . '/../resources/dist/calendar.css'),
            ],
            package: 'phpinnacle/tempo',
        );
    }
}

<?php

namespace PHPinnacle\Palette;

use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class PaletteServiceProvider extends PackageServiceProvider
{
    public const string PACKAGE = 'phpinnacle/palette';

    public static string $name = 'phpinnacle-palette';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasTranslations()
            ->hasViews();
    }

    public function packageBooted(): void
    {
        FilamentAsset::register(
            assets: [
                AlpineComponent::make('color-picker', __DIR__ . '/../resources/js/color-picker.js'),
                Css::make('color-picker', __DIR__ . '/../resources/css/color-picker.css'),
            ],
            package: self::PACKAGE,
        );
    }
}

<?php

namespace PHPinnacle\Iconic;

use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class IconicServiceProvider extends PackageServiceProvider
{
    public const string PACKAGE = 'phpinnacle/iconic';

    public static string $name = 'phpinnacle-iconic';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)->hasTranslations()->hasViews();
    }

    public function packageBooted(): void
    {
        FilamentAsset::register(
            assets: [
                Css::make('icon-picker', __DIR__ . '/../resources/css/icon-picker.css'),
                Js::make('icon-picker', __DIR__ . '/../resources/js/icon-picker.js'),
            ],
            package: self::PACKAGE,
        );
    }
}

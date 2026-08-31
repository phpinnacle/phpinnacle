<?php

namespace PHPinnacle\Stylus;

use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class StylusServiceProvider extends PackageServiceProvider
{
    public const string PACKAGE = 'phpinnacle/stylus';

    public static string $name = 'phpinnacle-stylus';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasTranslations()
            ->hasViews('stylus');
    }

    public function packageBooted(): void
    {
        FilamentAsset::register(
            assets: [
                Js::make('html-extensions', __DIR__ . '/../resources/dist/html-extensions.js')
                    ->module(),
                Js::make('description-list', __DIR__ . '/../resources/dist/description-list.js')
                    ->module(),
                Js::make('condition', __DIR__ . '/../resources/dist/condition.js')
                    ->module(),
                Js::make('twig-rich-editor', __DIR__ . '/../resources/dist/twig-rich-editor.js')
                    ->module()
                    ->loadedOnRequest(),
                Css::make('stylus-rich-editor', __DIR__ . '/../resources/css/rich-editor.css'),
            ],
            package: self::PACKAGE,
        );
    }
}

<?php

namespace PHPinnacle\Common;

use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class CommonServiceProvider extends PackageServiceProvider
{
    public static string $name = 'phpinnacle-common';

    public function configurePackage(Package $package): void
    {
        $package->name(self::$name)->hasTranslations()->hasViews();
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
            AlpineComponent::make('item-picker', __DIR__ . '/../resources/js/item-picker.js'),
            Css::make('item-picker', __DIR__ . '/../resources/css/item-picker.css'),
        ], 'phpinnacle/common');
    }
}

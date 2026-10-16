<?php

namespace PHPinnacle\Eidos;

use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
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
            ->hasRoute('web');
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

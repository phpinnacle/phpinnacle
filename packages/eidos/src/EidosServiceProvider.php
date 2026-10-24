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
            AlpineComponent::make('field-mapping', __DIR__ . '/../resources/js/field-mapping.js'),
            AlpineComponent::make('field-binding', __DIR__ . '/../resources/js/field-binding.js'),
            Css::make('field-mapping', __DIR__ . '/../resources/css/field-mapping.css'),
            Js::make('resources/js/json-schema-picker', __DIR__ . '/../resources/js/json-schema-picker.js')
                ->module()
                ->loadedOnRequest(),
            Js::make('resources/js/json-schema-tools', __DIR__ . '/../resources/js/json-schema-tools.js')
                ->module()
                ->loadedOnRequest(),
            Js::make('resources/js/json-schema-definition', __DIR__ . '/../resources/js/json-schema-definition.js')
                ->module()
                ->loadedOnRequest(),
            Js::make('resources/js/json-schema-presentation', __DIR__ . '/../resources/js/json-schema-presentation.js')
                ->module()
                ->loadedOnRequest(),
            Css::make('json-schema-picker', __DIR__ . '/../resources/css/json-schema-picker.css'),
        ], 'phpinnacle/eidos');

        FilamentAsset::register([
            Js::make('resources/js/json-path', __DIR__ . '/../../jason/resources/js/json-path.js')
                ->module()
                ->loadedOnRequest(),
        ], 'phpinnacle/jason');
    }
}

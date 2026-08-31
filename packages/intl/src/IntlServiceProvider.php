<?php

namespace PHPinnacle\Intl;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class IntlServiceProvider extends PackageServiceProvider
{
    public static string $name = 'phpinnacle-intl';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasTranslations();
    }
}

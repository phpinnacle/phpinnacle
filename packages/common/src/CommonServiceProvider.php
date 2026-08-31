<?php

namespace PHPinnacle\Common;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class CommonServiceProvider extends PackageServiceProvider
{
    public static string $name = 'phpinnacle-common';

    public function configurePackage(Package $package): void
    {
        $package->name(self::$name)->hasTranslations();
    }
}

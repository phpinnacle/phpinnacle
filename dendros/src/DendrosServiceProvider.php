<?php

namespace PHPinnacle\Dendros;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class DendrosServiceProvider extends PackageServiceProvider
{
    public static string $name = 'phpinnacle-dendros';

    public function configurePackage(Package $package): void
    {
        $package
            ->hasMigrations()
            ->name(static::$name);
    }
}

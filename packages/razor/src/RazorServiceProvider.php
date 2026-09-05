<?php

namespace PHPinnacle\Razor;

use Illuminate\Contracts\Foundation\Application;
use PHPinnacle\Money\Money;
use PHPinnacle\Razor\Engines\Handlebars;
use PHPinnacle\Razor\Engines\Twig;
use PHPinnacle\Razor\Services\Registry;
use PHPinnacle\Razor\Services\Renderer;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Twig\Environment;
use Twig\Extra\Intl\IntlExtension;
use Twig\Loader\ArrayLoader;
use Twig\TwigFilter;

class RazorServiceProvider extends PackageServiceProvider
{
    public static string $name = 'phpinnacle-razor';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->discoversMigrations()
            ->hasTranslations()
            ->hasConfigFile()
            ->hasAssets()
            ->hasRoutes('web')
            ->hasViews()
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->askToStarRepoOnGitHub('phpinnacle/razor');
            });
    }

    public function packageRegistered(): void
    {
        $this->app->bind(Twig::class, function () {
            $loader = new ArrayLoader([]);
            $environment = new Environment($loader);
            $environment->addExtension(new IntlExtension);
            $environment->addFilter(new TwigFilter('money', function ($value, $format = null, $currency = null) {
                return Money::parse($value, $currency)->format($format);
            }));

            return new Twig($environment);
        });

        $this->app->bind(Handlebars::class, Handlebars::instance(...));

        $this->app->singleton(Registry::class);
        $this->app->singleton(Renderer::class, function (Application $application) {
            $renderer = new Renderer;

            $renderer->register($application->make(Handlebars::class));
            $renderer->register($application->make(Twig::class));

            return $renderer;
        });
    }
}

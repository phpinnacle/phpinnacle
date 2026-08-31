<?php

namespace PHPinnacle\Money;

use Livewire\Livewire;
use PHPinnacle\Money\Formatters\EnglishFormatter;
use PHPinnacle\Money\Formatters\PolishFormatter;
use PHPinnacle\Money\Formatters\RussianFormatter;
use PHPinnacle\Money\Livewire\MoneySynth;
use PHPinnacle\Money\Services\MoneyFormatter;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class MoneyServiceProvider extends PackageServiceProvider
{
    public static string $name = 'phpinnacle-money';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasTranslations()
            ->hasViews();
    }

    public function packageBooted(): void
    {
        MoneyFormatter::learn('en', new EnglishFormatter);
        MoneyFormatter::learn('pl', new PolishFormatter);
        MoneyFormatter::learn('ru', new RussianFormatter);

        Livewire::propertySynthesizer(MoneySynth::class);
    }
}

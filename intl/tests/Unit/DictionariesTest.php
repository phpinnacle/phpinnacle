<?php

use Illuminate\Support\Facades\Validator;
use PHPinnacle\Intl\Countries;
use PHPinnacle\Intl\Filters\CountryFilter;
use PHPinnacle\Intl\Forms\CountryPicker;
use PHPinnacle\Intl\Forms\LanguagePicker;
use PHPinnacle\Intl\Forms\LocalePicker;
use PHPinnacle\Intl\Forms\TimezonePicker;
use PHPinnacle\Intl\Languages;
use PHPinnacle\Intl\Locales;
use PHPinnacle\Intl\Rules\CountryCode;
use PHPinnacle\Intl\Rules\LanguageCode;
use PHPinnacle\Intl\Timezones;
use Tests\TestCase;

uses(TestCase::class);

it('provides normalized international dictionaries', function () {
    expect(Countries::exists(' pl '))->toBeTrue()
        ->and(Countries::name('pl', 'en'))->toBe('Poland')
        ->and(Countries::name(Countries::OTHER, 'en'))->toBe('Other')
        ->and(Languages::exists('eng'))->toBeTrue()
        ->and(Locales::exists('en'))->toBeTrue()
        ->and(Locales::list('en')[Locales::OTHER])->toBe('Other')
        ->and(Timezones::exists('Europe/Warsaw'))->toBeTrue();
});

it('validates country and language codes', function () {
    $valid = Validator::make(
        ['country' => 'PL', 'language' => 'eng'],
        ['country' => [new CountryCode], 'language' => [new LanguageCode]],
    );
    $invalid = Validator::make(
        ['country' => [], 'language' => 'invalid'],
        ['country' => [new CountryCode], 'language' => [new LanguageCode]],
    );

    expect($valid->passes())->toBeTrue()
        ->and($invalid->fails())->toBeTrue();
});

it('provides Filament pickers and filters', function () {
    expect(CountryPicker::make('country')->getOptions())->toHaveKey('PL')
        ->and(LanguagePicker::make('language')->getOptions())->toHaveKey('eng')
        ->and(LocalePicker::make('locale')->getOptions())->toHaveKey('en')
        ->and(TimezonePicker::make('timezone')->getOptions())->toHaveKey('Europe/Warsaw')
        ->and(CountryFilter::make()->getName())->toBe('country');
});

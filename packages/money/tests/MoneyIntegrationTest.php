<?php

use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Number;
use Livewire\Mechanisms\HandleComponents\ComponentContext;
use PHPinnacle\Money\Forms\CurrencyPicker;
use PHPinnacle\Money\Forms\MoneyInput;
use PHPinnacle\Money\Livewire\MoneySynth;
use PHPinnacle\Money\Money;
use PHPinnacle\Money\Tables\MoneyColumn;
use Tests\TestCase;

uses(TestCase::class);

it('parses and formats money through the package services', function () {
    $money = Money::parse('19.90', 'USD');

    expect($money->amount)
        ->toBe(1990)
        ->and($money->decimal())
        ->toBe('19.90')
        ->and($money->format(locale: 'en'))
        ->toContain('19.90');
});

it('hydrates and mutates money through the Livewire synthesizer', function () {
    $synthesizer = new MoneySynth(new ComponentContext(null), 'price');
    $money = new Money(1990, 'USD');

    [$dehydrated] = $synthesizer->dehydrate($money);
    $hydrated = $synthesizer->hydrate($dehydrated);
    $synthesizer->set($hydrated, 'amount', '25.50');
    $synthesizer->set($hydrated, 'currency', 'EUR');

    expect($dehydrated)
        ->toBe(['amount' => '19.90', 'currency' => 'USD'])
        ->and($hydrated)
        ->toEqual(new Money(2550, 'EUR'));
});

it('provides the Filament money fields', function () {
    $moneyInput = MoneyInput::make('price')->currencies(['USD', 'EUR'], 'EUR');
    $currencyPicker = CurrencyPicker::make();

    expect($moneyInput->getCurrencies())
        ->toBe(['USD', 'EUR'])
        ->and($currencyPicker->getName())
        ->toBe('currency')
        ->and($currencyPicker->getOptions())
        ->toHaveKeys(['USD', 'EUR']);
});

it('formats Money values in Filament tables', function () {
    $table = Table::make($this->createStub(HasTable::class))
        ->defaultNumberLocale('ru')
        ->columns([
            MoneyColumn::make('price'),
        ]);

    $column = $table->getColumns()['price'];
    $money = new Money(1990, 'USD');

    expect($column->formatState($money))
        ->toBe(Number::currency(19.90, 'USD', 'ru'))
        ->and($column->formatState(null))
        ->toBeNull();
});

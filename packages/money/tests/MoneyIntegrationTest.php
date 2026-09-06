<?php

use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Number;
use Illuminate\Validation\ValidationException;
use Livewire\Component as LivewireComponent;
use Livewire\Mechanisms\HandleComponents\ComponentContext;
use PHPinnacle\Money\Forms\CurrencyPicker;
use PHPinnacle\Money\Forms\MoneyInput;
use PHPinnacle\Money\Livewire\MoneySynth;
use PHPinnacle\Money\Money;
use PHPinnacle\Money\Rules\MoneyRule;
use PHPinnacle\Money\Tables\MoneyColumn;
use Tests\TestCase;

uses(TestCase::class);

function money_validation_form(MoneyInput $field, mixed $state): Schema
{
    $livewire = new class extends LivewireComponent implements HasSchemas {
        use InteractsWithSchemas;

        /** @var array<string, mixed> */
        public array $data = [];
    };
    $livewire->setId('money-validation-test');
    $livewire->setName('money-validation-test');

    $schema = Schema::make($livewire)->statePath('data')->components([$field])->fill();
    $livewire->data = ['price' => $state, 'maximum' => new Money(100, 'USD')];

    return $schema;
}

it('parses and formats money through the package services', function () {
    $money = Money::parse('19.90', 'USD');

    expect($money->amount)
        ->toBe(1990)
        ->and($money->decimal())
        ->toBe('19.90')
        ->and($money->format(locale: 'en'))
        ->toContain('19.90');
});

it('carries rounding across decimal digits', function (string $value, int $expected) {
    expect(Money::parse($value, 'USD')->amount)->toBe($expected);
})->with([
    'positive carry' => ['99.995', 10_000],
    'negative carry' => ['-99.995', -10_000],
    'below rounding threshold' => ['99.994', 9999],
]);

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

it('validates form bounds through submission', function (
    string $method,
    bool $strict,
    array $accepted,
    string $boundType,
) {
    $bound = match ($boundType) {
        'integer' => 100,
        'money' => new Money(100, 'USD'),
        'reference' => 'data.maximum',
        'closure' => fn () => new Money(100, 'USD'),
    };

    foreach ([99, 100, 101] as $amount) {
        $field = MoneyInput::make('price')->{$method}($bound, strict: $strict);
        $form = money_validation_form($field, new Money($amount, 'USD')->toLivewire());

        if (in_array($amount, $accepted, true)) {
            expect($form->getState()['price'])->toEqual(new Money($amount, 'USD'));
        } else {
            expect($form->getState(...))->toThrow(ValidationException::class);
        }
    }
})->with([
    'minimum' => ['greater', false, [100, 101]],
    'strict minimum' => ['greater', true, [101]],
    'maximum' => ['lesser', false, [99, 100]],
    'strict maximum' => ['lesser', true, [99]],
])->with(['integer', 'money', 'reference', 'closure']);

it('validates equality bounds without coercing literal values to field names', function (
    string $method,
    int $amount,
    bool $passes,
) {
    $form = money_validation_form(MoneyInput::make('price')->{$method}(100), new Money($amount, 'USD')->toLivewire());

    if ($passes) {
        expect($form->getState()['price'])->toEqual(new Money($amount, 'USD'));
    } else {
        expect($form->getState(...))->toThrow(ValidationException::class);
    }
})->with([
    ['equal',    100, true],
    ['equal',    101, false],
    ['notEqual', 100, false],
    ['notEqual', 101, true],
]);

it('requires a positive amount only while required is enabled', function (bool $required, bool $closure) {
    $condition = $closure ? fn () => $required : $required;
    $field = MoneyInput::make('price')->nullable()->required($condition);
    $form = money_validation_form($field, Money::zero('USD')->toLivewire());

    if ($required) {
        expect($form->getState(...))->toThrow(ValidationException::class);
    } else {
        expect($form->getState()['price'])->toBeNull();
    }

    $positive = money_validation_form(
        MoneyInput::make('price')->required($condition)->lesser(200),
        new Money(100, 'USD')->toLivewire(),
    );
    expect($positive->getState()['price'])->toEqual(new Money(100, 'USD'));
})->with([true, false])->with([true, false]);

it('reports malformed money input as a translated validation failure', function (mixed $value) {
    $validator = Validator::make(['price' => $value], ['price' => [MoneyRule::gte(0)]]);

    expect($validator->errors()->first('price'))
        ->toBe(__('phpinnacle-money::validation.money.invalid', ['attribute' => 'price']));
})->with([
    'boolean' => [true],
    'float' => [1.5],
    'object' => [new stdClass],
    'missing amount' => [['currency' => 'USD']],
    'missing currency' => [['amount' => '1.00']],
    'nested amount' => [['amount' => [], 'currency' => 'USD']],
    'invalid amount' => [['amount' => 'invalid', 'currency' => 'USD']],
    'invalid currency' => [['amount' => '1.00', 'currency' => 'invalid']],
    'non-string currency' => [['amount' => '1.00', 'currency' => []]],
]);

it('validates referenced input and preserves nullable comparison semantics', function () {
    $invalid = Validator::make([
        'price' => new Money(100, 'USD'),
        'maximum' => ['amount' => [], 'currency' => 'USD'],
    ], ['price' => [MoneyRule::lte('maximum')]]);

    expect($invalid->errors()->first('price'))
        ->toBe(__('phpinnacle-money::validation.money.invalid', ['attribute' => 'price']))
        ->and(Validator::make(['price' => null], ['price' => ['nullable', MoneyRule::gt(0)]])->passes())
        ->toBeTrue()
        ->and(Validator::make(['price' => new Money(100, 'USD')], ['price' => [MoneyRule::lte('maximum')]])->passes())
        ->toBeTrue();
});

it('preserves currency comparison semantics at the validation boundary', function () {
    $value = new Money(100, 'USD');

    expect(Validator::make(['price' => $value], ['price' => [MoneyRule::gt(Money::zero('EUR'))]])->passes())
        ->toBeTrue()
        ->and(Validator::make(['price' => $value], ['price' => [MoneyRule::gt(new Money(50, 'EUR'))]])->fails())
        ->toBeTrue()
        ->and(
            Validator::make([
                'price' => $value,
                'maximum' => ['amount' => '2.00', 'currency' => null],
            ], ['price' => [MoneyRule::lte('maximum')]])->fails(),
        )
        ->toBeTrue();
});

it('combines minimum and maximum bounds with the positive requirement', function () {
    foreach ([0, 100, 150, 200, 201] as $amount) {
        $form = money_validation_form(
            MoneyInput::make('price')->greater(100)->required()->lesser(200),
            new Money($amount, 'USD')->toLivewire(),
        );

        if ($amount >= 100 && $amount <= 200) {
            expect($form->getState()['price']->amount)->toBe($amount);
        } else {
            expect($form->getState(...))->toThrow(ValidationException::class);
        }
    }
});

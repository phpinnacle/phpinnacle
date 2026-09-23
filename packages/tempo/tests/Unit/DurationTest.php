<?php

use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ViewErrorBag;
use Livewire\Component as LivewireComponent;
use PHPinnacle\Tempo\Forms\Duration;
use PHPinnacle\Tempo\Rules\ValidDuration;
use Tests\TestCase;

uses(TestCase::class);

it('validates the selected storage format', function () {
    foreach ([0, 5400, ValidDuration::MAX_SECONDS] as $seconds) {
        expect(
            Validator::make(['duration' => $seconds], [
                'duration' => [new ValidDuration('seconds')],
            ])->passes(),
        )->toBeTrue();
    }

    foreach (['0s', '1d 2h 30m', '90m', '1h30m'] as $duration) {
        expect(
            Validator::make(['duration' => $duration], [
                'duration' => [new ValidDuration('string')],
            ])->passes(),
        )->toBeTrue();
    }

    expect(ValidDuration::parseString('1d 2h 30m'))->toBe(95_400);
});

it('rejects invalid durations at the form boundary', function () {
    foreach ([-1, 1.5, '5400', [], ValidDuration::MAX_SECONDS + 1] as $seconds) {
        expect(
            Validator::make(['duration' => $seconds], [
                'duration' => [new ValidDuration('seconds')],
            ])->fails(),
        )->toBeTrue();
    }

    foreach (['1 month', '1.5h', '-1h', '01h', '1x', '9007199254740992s', []] as $duration) {
        expect(
            Validator::make(['duration' => $duration], [
                'duration' => [new ValidDuration('string')],
            ])->fails(),
        )->toBeTrue();
    }
});

it('configures the duration field and evaluates its format', function () {
    $options = new class {
        /** @var 'seconds'|'string' */
        public string $format = 'seconds';
    };

    $field = Duration::make('timeout')->storeAs(fn () => $options->format);

    expect($field->getView())
        ->toBe('phpinnacle-tempo::forms.duration')
        ->and($field->getStorageFormat())
        ->toBe('seconds')
        ->and($field->getUnits())
        ->toBe(['day', 'hour', 'minute', 'second'])
        ->and($field->getPresets()['hour'])
        ->toBe('1h')
        ->and($field->getQuickValues()['day'])
        ->toBe([0, 1, 2, 7, 14, 30]);

    expect(
        Validator::make(['timeout' => 3600], [
            'timeout' => $field->getValidationRules(),
        ])->passes(),
    )->toBeTrue();

    $options->format = 'string';

    expect($field->getStorageFormat())
        ->toBe('string')
        ->and(array_map(get_debug_type(...), $field->getValidationRules()))
        ->toContain(ValidDuration::class)
        ->and($field->presets([])->getPresets())
        ->toBe([]);

    expect($field->quickValues(['day' => [0, 1, 3, 7]])->getQuickValues())
        ->toMatchArray([
            'day' => [0, 1, 3, 7],
            'hour' => [0, 1, 2, 4, 8, 12],
        ]);

    expect(
        Validator::make(['timeout' => '1h'], [
            'timeout' => $field->getValidationRules(),
        ])->passes(),
    )->toBeTrue();
});

it('limits duration values to configured units', function () {
    $field = Duration::make('timeout')->units(['minute', 'second']);

    expect($field->getUnits())->toBe(['minute', 'second']);

    foreach ([0, 90, 5400] as $seconds) {
        expect(
            Validator::make(['timeout' => $seconds], [
                'timeout' => $field->getValidationRules(),
            ])->passes(),
        )->toBeTrue();
    }

    $field->units(['minute']);

    expect(
        Validator::make(['timeout' => 90], [
            'timeout' => $field->getValidationRules(),
        ])->fails(),
    )->toBeTrue();

    expect(
        Validator::make(['timeout' => 120], [
            'timeout' => $field->getValidationRules(),
        ])->passes(),
    )->toBeTrue();

    $field->storeAs('string')->units(['minute', 'second']);

    expect(ValidDuration::parseString('90m', $field->getUnits()))
        ->toBe(5400)
        ->and(ValidDuration::parseString('1h 30m', $field->getUnits()))
        ->toBeNull();

    expect(
        Validator::make(['timeout' => '1h'], [
            'timeout' => $field->getValidationRules(),
        ])->fails(),
    )->toBeTrue();
});

it('translates displayed unit suffixes without changing stored units', function () {
    expect(trans('phpinnacle-tempo::duration.suffixes.hour', locale: 'pl'))
        ->toBe('g')
        ->and(trans('phpinnacle-tempo::duration.suffixes.day', locale: 'ru'))
        ->toBe('д')
        ->and(trans('phpinnacle-tempo::duration.suffixes.hour', locale: 'ru'))
        ->toBe('ч')
        ->and(ValidDuration::parseString('1h', ['hour']))
        ->toBe(3600);
});

it('renders the configured field inside a Filament form', function () {
    view()->share('errors', new ViewErrorBag);

    $livewire = new class extends LivewireComponent implements HasSchemas {
        use InteractsWithSchemas;

        /** @var array<string, mixed> */
        public array $data = [];
    };

    $livewire->setId('tempo-duration-test');
    $livewire->setName('tempo-duration-test');

    $field = Duration::make('timeout')->storeAs('string');

    Schema::make($livewire)
        ->statePath('data')
        ->components([$field])
        ->fill();

    expect($field->toHtml())
        ->toContain('tempoDuration({')
        ->toContain('storageFormat:')
        ->toContain('quickValues:')
        ->toContain('tempo-duration__text-input')
        ->toContain('tempo-duration__part');
});

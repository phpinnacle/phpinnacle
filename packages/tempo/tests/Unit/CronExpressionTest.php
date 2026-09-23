<?php

use Illuminate\Support\Facades\Validator;
use PHPinnacle\Tempo\Forms\CronExpression;
use PHPinnacle\Tempo\Rules\ValidCronExpression;
use Tests\TestCase;

uses(TestCase::class);

it('accepts preset and complex cron expressions', function () {
    foreach ([
        '* * * * *',
        '*/15 9 * * 1',
        '5,20 8-18/2 * 1,6 1-5',
        '*/99 * * * *',
        '0 23 ? * MON-FRI',
        '0 23 L * *',
        '0 23 L * ?',
        '0 9 * JAN,MAR mon',
    ] as $expression) {
        expect(
            Validator::make(['schedule' => $expression], [
                'schedule' => ['required', new ValidCronExpression],
            ])->passes(),
        )->toBeTrue();
    }
});

it('rejects malformed user cron input at the validation boundary', function () {
    foreach ([
        [],
        '60 * * * *',
        '*/0 * * * *',
        '*/01 * * * *',
        '0/15 * * * *',
        '* 24 * * *',
        '* * 0 * *',
        '* * * * 8',
        '1-0 * * * *',
        '0 23 ? * MON-FUNDAY',
        '0 23 ? * ?',
        '0 23 ? * MON,?',
        '0 23 MON * FRI',
        '0 23 1,L * *',
        '0 23 L-1 * *',
        '0 23 L * L',
        '* * * *',
    ] as $expression) {
        expect(
            Validator::make(['schedule' => $expression], [
                'schedule' => ['required', new ValidCronExpression],
            ])->fails(),
        )->toBeTrue();
    }
});

it('provides a Filament form field with cron validation', function () {
    $field = CronExpression::make('schedule');

    expect($field->getView())
        ->toBe('phpinnacle-tempo::forms.cron-expression')
        ->and($field->getGridColumns())
        ->toBe(['minute' => 6, 'hour' => 4, 'day' => 4, 'month' => 3, 'weekday' => 2])
        ->and($field->gridColumns(['hour' => 5])->getGridColumns())
        ->toBe(['minute' => 6, 'hour' => 5, 'day' => 4, 'month' => 3, 'weekday' => 2])
        ->and($field->showsDescription())
        ->toBeFalse()
        ->and($field->showDescription()->showsDescription())
        ->toBeTrue()
        ->and($field->showDescription(false)->showsDescription())
        ->toBeFalse()
        ->and($field->showsDayOfWeek())
        ->toBeTrue()
        ->and($field->showDayOfWeek(false)->showsDayOfWeek())
        ->toBeFalse()
        ->and($field->showDayOfWeek()->showsDayOfWeek())
        ->toBeTrue()
        ->and($field->showDayOfWeek(fn () => false)->showsDayOfWeek())
        ->toBeFalse()
        ->and($field->getDefaultMode())
        ->toBe('visual')
        ->and($field->defaultMode('expression')->getDefaultMode())
        ->toBe('expression')
        ->and($field->getPresets()['weekly'])
        ->toBe('0 9 * * 1')
        ->and($field->presets(['Late weekdays' => '0 23 ? * MON-FRI'])->getPresets())
        ->toBe(['Late weekdays' => '0 23 ? * MON-FRI'])
        ->and($field->presets([])->getPresets())
        ->toBe([])
        ->and(array_map(get_debug_type(...), $field->getValidationRules()))
        ->toContain(ValidCronExpression::class);
});

it('evaluates callback options when the field is rendered', function () {
    $options = new class {
        public bool $description = false;

        public bool $weekday = false;

        /** @var 'visual'|'expression' */
        public string $mode = 'visual';

        /** @var array<string, int> */
        public array $columns = ['hour' => 5];

        /** @var array<string, string> */
        public array $presets = ['Daily' => '0 9 * * *'];
    };

    $field = CronExpression::make('schedule')
        ->showDescription(fn () => $options->description)
        ->showDayOfWeek(fn () => $options->weekday)
        ->defaultMode(fn () => $options->mode)
        ->gridColumns(fn () => $options->columns)
        ->presets(fn () => $options->presets);

    expect($field->showsDescription())
        ->toBeFalse()
        ->and($field->showsDayOfWeek())
        ->toBeFalse()
        ->and($field->getDefaultMode())
        ->toBe('visual')
        ->and($field->getGridColumns())
        ->toBe(['minute' => 6, 'hour' => 5, 'day' => 4, 'month' => 3, 'weekday' => 2])
        ->and($field->getPresets())
        ->toBe(['Daily' => '0 9 * * *']);

    $options->description = true;
    $options->weekday = true;
    $options->mode = 'expression';
    $options->columns = ['month' => 4];
    $options->presets = [];

    expect($field->showsDescription())
        ->toBeTrue()
        ->and($field->showsDayOfWeek())
        ->toBeTrue()
        ->and($field->getDefaultMode())
        ->toBe('expression')
        ->and($field->getGridColumns())
        ->toBe(['minute' => 6, 'hour' => 4, 'day' => 4, 'month' => 4, 'weekday' => 2])
        ->and($field->getPresets())
        ->toBe([]);
});

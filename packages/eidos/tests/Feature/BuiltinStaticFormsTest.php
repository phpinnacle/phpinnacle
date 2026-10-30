<?php

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\ValidationException;
use PHPinnacle\Eidos\Forms\BuiltinStaticForms;
use PHPinnacle\Eidos\Forms\FieldMapping;
use PHPinnacle\Eidos\Forms\JsonSchema;
use PHPinnacle\Eidos\Tests\Fixtures\MappingState;
use PHPinnacle\Eidos\Tests\Fixtures\SchemaHost;
use Tests\TestCase;

require_once __DIR__ . '/../Fixtures/MappingState.php';
require_once __DIR__ . '/../Fixtures/SchemaHost.php';

uses(TestCase::class);

/** @param array<string, mixed> $property */
function builtin_static_schema(array $property, mixed $value = null): Schema
{
    $field = FieldMapping::make('mapping')
        ->jsonSchema(
            source: JsonSchema::fromArray(['properties' => ['source' => ['type' => 'string']]]),
            target: JsonSchema::fromArray([
                'properties' => ['target' => $property],
                'required' => ['target'],
            ]),
        )
        ->withBuiltinStaticForms();
    $schema = Schema::make(SchemaHost::make())->statePath('data')->components([$field]);
    $schema->fill(['mapping' => MappingState::mapping(['target' => MappingState::value($value)])]);

    return $schema;
}

it('registers native editors for distinct built-in field types', function (array $property, string $class) {
    $schema = builtin_static_schema($property);
    $field = $schema->getComponent('mapping')->getStaticFormSchema('target')->getComponent('value');

    expect($field)->toBeInstanceOf($class);
})->with([
    'string' => [['type' => 'string'], TextInput::class],
    'text' => [['type' => 'string', 'format' => 'text'], Textarea::class],
    'boolean' => [['type' => 'boolean'], Toggle::class],
    'nullable boolean' => [['type' => ['boolean', 'null']], Select::class],
    'typed array' => [['type' => 'array', 'items' => ['type' => 'integer']], Repeater::class],
    'null' => [['type' => 'null'], TextInput::class],
    'enum' => [['type' => 'string', 'enum' => ['draft', 'active']], Select::class],
    'date' => [['type' => 'string', 'format' => 'date'], DatePicker::class],
    'time' => [['type' => 'string', 'format' => 'time'], TimePicker::class],
    'date-time' => [['type' => 'string', 'format' => 'date-time'], DateTimePicker::class],
]);

it('round trips built-in static values with their native types', function (array $property, mixed $value) {
    $state = builtin_static_schema($property, $value)->getState()['mapping'];

    expect($state)->toBe(MappingState::mapping(['target' => MappingState::value($value)], [], ['source']));
})->with([
    'string' => [['type' => 'string'], '007'],
    'text' => [['type' => 'string', 'format' => 'text'], "First line\nSecond line"],
    'integer' => [['type' => 'integer'], 0],
    'number' => [['type' => 'number'], 1.25],
    'boolean' => [['type' => 'boolean'], false],
    'nullable boolean' => [['type' => ['boolean', 'null']], null],
    'nullable string' => [['type' => ['string', 'null']], null],
    'null' => [['type' => 'null'], null],
    'integer array' => [['type' => 'array', 'items' => ['type' => 'integer']], [0, 2]],
    'number array' => [['type' => 'array', 'items' => ['type' => 'number']], [0.0, 1.25]],
    'boolean array' => [['type' => 'array', 'items' => ['type' => 'boolean']], [false, true]],
    'nullable boolean items' => [
        ['type' => 'array', 'items' => ['type' => ['boolean', 'null']]],
        [false, null, true],
    ],
    'enum items' => [['type' => 'array', 'items' => ['type' => 'string', 'enum' => ['one', 'two']]], ['two']],
    'date items' => [
        ['type' => 'array', 'items' => ['type' => 'string', 'format' => 'date']],
        ['2026-10-23'],
    ],
    'time items' => [
        ['type' => 'array', 'items' => ['type' => 'string', 'format' => 'time']],
        ['14:30:00'],
    ],
    'nullable items' => [['type' => 'array', 'items' => ['type' => ['string', 'null']]], ['one', null]],
    'empty typed array' => [['type' => 'array', 'items' => ['type' => 'string']], []],
    'enum' => [['type' => 'string', 'enum' => ['draft', 'active']], 'active'],
    'uuid' => [
        ['type' => 'string', 'format' => 'uuid'],
        '550e8400-e29b-41d4-a716-446655440000',
    ],
    'email' => [['type' => 'string', 'format' => 'email'], 'user@example.com'],
    'phone' => [['type' => 'string', 'format' => 'phone'], '+48123456789'],
    'date' => [['type' => 'string', 'format' => 'date'], '2026-10-22'],
    'time' => [['type' => 'string', 'format' => 'time'], '14:30:00'],
    'date-time' => [
        ['type' => 'string', 'format' => 'date-time'],
        '2026-10-22T14:30:00+00:00',
    ],
]);

it('uses item formats and constraints inside repeaters', function () {
    $schema = builtin_static_schema([
        'type' => 'array',
        'minItems' => 1,
        'maxItems' => 10,
        'items' => ['type' => 'string', 'format' => 'email', 'maxLength' => 64],
    ], ['user@example.com']);
    $repeater = $schema->getComponent('mapping')->getStaticFormSchema('target')->getComponent('value');
    $item = $repeater->getSimpleField();

    expect($repeater)
        ->toBeInstanceOf(Repeater::class)
        ->getMinItems()
        ->toBe(1)
        ->getMaxItems()
        ->toBe(10)
        ->and($item)
        ->toBeInstanceOf(TextInput::class)
        ->getType()
        ->toBe('email')
        ->getMaxLength()
        ->toBe(64);
});

it('validates array counts and nested item values', function (array $property, array $value) {
    $schema = builtin_static_schema($property, $value);

    expect($schema->getState(...))->toThrow(ValidationException::class);
})->with([
    'minimum items' => [['type' => 'array', 'minItems' => 1, 'items' => ['type' => 'string']], []],
    'maximum items' => [['type' => 'array', 'maxItems' => 1, 'items' => ['type' => 'string']], ['one', 'two']],
    'unique strings' => [['type' => 'array', 'uniqueItems' => true, 'items' => ['type' => 'string']], ['one', 'one']],
    'item length' => [['type' => 'array', 'items' => ['type' => 'string', 'maxLength' => 3]], ['long']],
    'item format' => [['type' => 'array', 'items' => ['type' => 'string', 'format' => 'email']], ['invalid']],
    'item integer' => [['type' => 'array', 'items' => ['type' => 'integer']], [1.5]],
]);

it('validates submitted values through built-in fields', function (array $property, mixed $value) {
    $schema = builtin_static_schema($property);
    $schema->getComponent('mapping')->getStaticFormSchema('target')->getComponent('value')->rawState($value);

    expect($schema->getState(...))->toThrow(ValidationException::class);
})->with([
    'required' => [['type' => 'string'], ''],
    'length' => [['type' => 'string', 'maxLength' => 3], 'long'],
    'required text' => [['type' => 'string', 'format' => 'text'], ''],
    'minimum text length' => [['type' => 'string', 'format' => 'text', 'minLength' => 3], 'ab'],
    'maximum text length' => [['type' => 'string', 'format' => 'text', 'maxLength' => 3], 'long'],
    'uuid' => [['type' => 'string', 'format' => 'uuid'], 'invalid'],
    'email' => [['type' => 'string', 'format' => 'email'], 'invalid'],
    'phone' => [['type' => 'string', 'format' => 'phone'], 'letters'],
    'integer' => [['type' => 'integer'], '1.5'],
    'minimum' => [['type' => 'number', 'minimum' => 1], '0'],
    'maximum' => [['type' => 'number', 'maximum' => 1], '2'],
    'enum' => [['type' => 'string', 'enum' => ['draft']], 'other'],
]);

it('leaves unsupported built-in static types available for connections', function (array $property) {
    $field = FieldMapping::make('mapping')
        ->jsonSchema(
            source: JsonSchema::fromArray(['properties' => ['source' => $property]]),
            target: JsonSchema::fromArray(['properties' => ['target' => $property], 'required' => ['target']]),
        )
        ->withBuiltinStaticForms();
    $schema = Schema::make(SchemaHost::make())->statePath('data')->components([$field]);
    $schema->fill();

    expect($field->getStaticFormSchema('target'))->toBeNull();
    expect($field->getStaticDefaults())->toBe([]);
    expect($schema->getState(...))->toThrow(ValidationException::class);

    $schema->fill(['mapping' => MappingState::mapping(['target' => MappingState::value([])])]);
    expect($schema->getState(...))->toThrow(ValidationException::class);

    $mapping = MappingState::mapping(['target' => MappingState::source('source')]);
    $schema->fill(['mapping' => $mapping]);
    expect($schema->getState()['mapping'])->toBe($mapping);
})->with([
    'object' => [['type' => 'object']],
    'nullable object' => [['type' => ['object', 'null']]],
    'untyped array' => [['type' => 'array']],
    'nested array' => [['type' => 'array', 'items' => ['type' => 'array', 'items' => ['type' => 'string']]]],
    'object array' => [['type' => 'array', 'items' => ['type' => 'object']]],
    'mixed items' => [['type' => 'array', 'items' => ['type' => ['string', 'number']]]],
    'nullable array' => [['type' => ['array', 'null'], 'items' => ['type' => 'string']]],
]);

it('replaces built-in static forms with a target factory', function (string $type) {
    $schema = builtin_static_schema(['type' => $type], 'custom');
    $field = $schema->getComponent('mapping');
    $field->staticForms(fn () => TextInput::make('value')->maxLength(2));

    expect($field->getStaticFormSchema('target')->getComponent('value')->getMaxLength())->toBe(2);
    expect($schema->getState(...))->toThrow(ValidationException::class);
})->with(['string', 'array', 'object']);

it('reuses built-in editors inside a target factory', function () {
    $field = FieldMapping::make('mapping')
        ->jsonSchema(target: JsonSchema::fromArray([
            'properties' => [
                'id' => ['type' => 'string'],
                'active' => ['type' => 'boolean'],
            ],
        ]))
        ->staticForms(fn (string $target, JsonSchema $property) => $target === 'id'
            ? null
            : BuiltinStaticForms::make($property, false));
    Schema::make(SchemaHost::make())->statePath('data')->components([$field])->fill(['mapping' => []]);

    expect($field->getStaticFormSchema('id'))
        ->toBeNull()
        ->and($field->getStaticFormSchema('active')->getComponent('value'))
        ->toBeInstanceOf(Toggle::class)
        ->and($field->getStaticDefaults())
        ->toBe(['active' => false]);
});

it('renders destination static forms without source fields', function () {
    $field = FieldMapping::make('mapping')
        ->jsonSchema(
            source: JsonSchema::fromArray(['type' => 'object']),
            target: JsonSchema::fromArray([
                'properties' => [
                    'name' => ['type' => 'string', 'title' => 'Name', 'maxLength' => 255],
                    'active' => ['type' => 'boolean', 'title' => 'Active'],
                ],
            ]),
        )
        ->withBuiltinStaticForms();
    $schema = Schema::make(SchemaHost::make())->statePath('data')->components([$field]);
    $schema->fill(['mapping' => []]);
    view()->share('errors', new ViewErrorBag);

    expect($schema->toHtml())
        ->toContain('role="switch"', 'maxlength="255"')
        ->not->toContain(__('phpinnacle-eidos::schema-forms.mapping.empty'));
});

it('labels enum options with x-enum while saving and validating their original values', function () {
    $property = [
        'type' => 'string',
        'enum' => ['draft', 'active', 'archived'],
        'x-enum' => ['draft' => 'Draft order', 'active' => 'Active order', 'other' => 'Not an option'],
    ];
    $schema = builtin_static_schema($property, 'active');
    $field = $schema->getComponent('mapping')->getStaticFormSchema('target')->getComponent('value');

    expect($field->getOptions())->toBe([
        'draft' => 'Draft order',
        'active' => 'Active order',
        'archived' => 'archived',
    ]);
    expect($schema->getState()['mapping'][0]['static'])->toBe('active');
    expect(builtin_static_schema($property, 'Active order')->getState(...))->toThrow(ValidationException::class);
});

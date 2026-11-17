<?php

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema as FilamentSchema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema as DatabaseSchema;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\ValidationException;
use PHPinnacle\Eidos\Forms\BuiltinStaticForms;
use PHPinnacle\Eidos\Forms\FieldMapping;
use PHPinnacle\Eidos\Forms\ModelLookup;
use PHPinnacle\Eidos\Tests\Fixtures\LookupCategory;
use PHPinnacle\Eidos\Tests\Fixtures\MappingState;
use PHPinnacle\Eidos\Tests\Fixtures\SchemaHost;
use PHPinnacle\Jason\Schema;
use Tests\TestCase;

require_once __DIR__ . '/../Fixtures/MappingState.php';
require_once __DIR__ . '/../Fixtures/SchemaHost.php';
require_once __DIR__ . '/../Fixtures/LookupCategory.php';

uses(TestCase::class);

function builtin_lookup_records(): void
{
    config()->set('database.connections.lookup', config('database.connections.sqlite'));
    DatabaseSchema::connection('lookup')->create('lookup_categories', function (Blueprint $table) {
        $table->id('category_id');
        $table->string('name');
        $table->string('title');
        $table->string('code');
        $table->boolean('listed');
    });
    LookupCategory::query()->insert([
        ['category_id' => 0, 'name' => 'Zero', 'title' => 'None', 'code' => '0', 'listed' => true],
        ['category_id' => 1, 'name' => '<Alpha>', 'title' => 'First', 'code' => '0001', 'listed' => true],
        ['category_id' => 2, 'name' => 'Beta', 'title' => 'Second', 'code' => '0002', 'listed' => true],
        ['category_id' => 3, 'name' => 'Hidden', 'title' => 'Hidden', 'code' => '0003', 'listed' => false],
    ]);
}

it('searches lookup labels and resolves reopened selections on the model connection and scopes', function () {
    builtin_lookup_records();
    $schema = builtin_static_schema(['type' => 'integer', 'x-lookup' => ['model' => LookupCategory::class]], 1);
    $field = $schema->getComponent('mapping')->getStaticFormSchema('target')->getComponent('value');

    expect($field)->toBeInstanceOf(Select::class);
    expect($field->getOptions())->toBe([1 => '<Alpha>', 2 => 'Beta', 0 => 'Zero']);
    expect($field->getSearchResults('Alpha'))->toBe([1 => '<Alpha>']);
    expect($field->getSearchResults('Hidden'))->toBe([]);
    expect($field->getSearchResults("' OR 1=1 --"))->toBe([]);
    expect($field->getOptionLabel())->toBe('<Alpha>');
    expect($schema->getState()['mapping'][0]['static'])->toBe(1);
});

it('limits preloaded and searched lookup records while resolving selections outside the first page', function (
    ?int $limit,
    int $expectedCount,
) {
    builtin_lookup_records();
    LookupCategory::query()->insert(array_map(
        fn (int $id) => [
            'category_id' => $id,
            'name' => 'Category ' . $id,
            'title' => 'Category ' . $id,
            'code' => (string) $id,
            'listed' => true,
        ],
        range(4, 24),
    ));
    $property = Schema::fromArray(['type' => 'integer', 'x-lookup' => ['model' => LookupCategory::class]]);
    $field = $limit === null
        ? ModelLookup::picker($property, 'value')
        : ModelLookup::picker($property, 'value', $limit);
    $form = FilamentSchema::make(SchemaHost::make())->statePath('data')->components([$field]);
    $form->fill(['value' => 0]);

    expect($field->getOptions())->toHaveCount($expectedCount)->not->toHaveKey(0)->not->toHaveKey(3);
    expect($field->getSearchResults('Category'))->toHaveCount($expectedCount);
    expect($field->getOptionLabel())->toBe('Zero');
    expect($form->getState()['value'])->toBe(0);
})->with([
    'default limit' => [null, 20],
    'custom limit' => [2, 2],
]);

it('uses custom lookup labels search attributes and value keys', function () {
    builtin_lookup_records();
    $schema = builtin_static_schema([
        'type' => 'string',
        'x-lookup' => [
            'model' => LookupCategory::class,
            'key' => 'code',
            'label' => 'title',
            'search' => ['name', 'code'],
        ],
    ], '0002');
    $field = $schema->getComponent('mapping')->getStaticFormSchema('target')->getComponent('value');

    expect($field->getSearchResults('0001'))->toBe(['0001' => 'First']);
    expect($field->getSearchResults('Beta'))->toBe(['0002' => 'Second']);
    expect($field->getSearchResults('0003'))->toBe([]);
    expect($field->getOptionLabel())->toBe('Second');
    expect($schema->getState()['mapping'][0]['static'])->toBe('0002');

    $schema = builtin_static_schema([
        'type' => 'string',
        'x-lookup' => ['model' => LookupCategory::class, 'key' => 'code', 'label' => 'title'],
    ], '0002');
    expect(
        $schema
            ->getComponent('mapping')
            ->getStaticFormSchema('target')
            ->getComponent('value')
            ->getSearchResults('Second'),
    )
        ->toBe(['0002' => 'Second']);
});

it('preserves lookup key types and nullable values through hydration validation and dehydration', function (
    array $types,
    string $key,
    mixed $value,
    ?string $submitted,
) {
    builtin_lookup_records();
    $property = [
        'allOf' => [[
            'type' => $types,
            'x-lookup' => ['model' => LookupCategory::class, 'key' => $key],
        ]],
    ];
    $schema = builtin_static_schema($property, $value);
    $field = $schema->getComponent('mapping')->getStaticFormSchema('target')->getComponent('value');
    $field->rawState($submitted);

    $mapping = $schema->getState()['mapping'];

    expect($mapping[0]['static'])->toBe($value);
    $reopened = builtin_static_schema($property, $mapping[0]['static']);
    expect($reopened->getComponent('mapping')->getStaticFormSchema('target')->getComponent('value')->getOptionLabel())
        ->toBe($field->getOptionLabel());
    expect($reopened->getState()['mapping'])->toBe($mapping);
})->with([
    'integer key' => [['integer'], 'category_id', 2, '2'],
    'zero integer key' => [['integer'], 'category_id', 0, '0'],
    'string key' => [['string'], 'code', '0002', '0002'],
    'numeric string key' => [['string'], 'code', '0', '0'],
    'null integer' => [['integer', 'null'], 'category_id', null, null],
    'cleared integer' => [['integer', 'null'], 'category_id', null, ''],
    'null string' => [['string', 'null'], 'code', null, null],
]);

it('rejects missing scoped-out and required empty lookup choices', function (?string $value) {
    builtin_lookup_records();
    $schema = builtin_static_schema(['type' => 'integer', 'x-lookup' => ['model' => LookupCategory::class]]);
    $schema->getComponent('mapping')->getStaticFormSchema('target')->getComponent('value')->rawState($value);

    expect($schema->getState(...))->toThrow(ValidationException::class);
})->with(['999', '3', null, '']);

it('offers only projected enum values in native static editors', function () {
    $enumerated = Schema::allOf([
        Schema::fromArray(['type' => 'string', 'enum' => ['draft', 'active']]),
        Schema::fromArray(['enum' => ['active']]),
    ]);

    expect(BuiltinStaticForms::make($enumerated, false)->getOptions())->toBe(['active' => 'active']);
});

/** @param array<string, mixed> $property */
function builtin_static_schema(array $property, mixed $value = null): FilamentSchema
{
    $field = FieldMapping::make('mapping')
        ->jsonSchema(
            source: Schema::fromArray(['properties' => ['source' => ['type' => 'string']]]),
            target: Schema::fromArray([
                'properties' => ['target' => $property],
                'required' => ['target'],
            ]),
        )
        ->withBuiltinStaticForms();
    $schema = FilamentSchema::make(SchemaHost::make())->statePath('data')->components([$field]);
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
    'projected enum' => [
        ['allOf' => [['type' => 'string', 'enum' => ['draft', 'active']], ['enum' => ['active']]]],
        'active',
    ],
    'projected length' => [['allOf' => [['type' => 'string', 'minLength' => 2], ['maxLength' => 5]]], 'valid'],
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

it('validates array counts and nested item values', function (array $property, array $value) {
    $schema = builtin_static_schema($property, $value);

    expect($schema->getState(...))->toThrow(ValidationException::class);
})->with([
    'minimum items' => [['type' => 'array', 'minItems' => 1, 'items' => ['type' => 'string']], []],
    'maximum items' => [['type' => 'array', 'maxItems' => 1, 'items' => ['type' => 'string']], ['one', 'two']],
    'unique strings' => [['type' => 'array', 'uniqueItems' => true, 'items' => ['type' => 'string']], ['one', 'one']],
    'referenced array maximum' => [
        [
            '$defs' => ['value' => ['type' => 'array', 'maxItems' => 1, 'items' => ['type' => 'string']]],
            '$ref' => '#/properties/target/$defs/value',
        ],
        ['one', 'two'],
    ],
    'referenced unique strings' => [
        [
            '$defs' => ['value' => ['type' => 'array', 'uniqueItems' => true, 'items' => ['type' => 'string']]],
            '$ref' => '#/properties/target/$defs/value',
        ],
        ['one', 'one'],
    ],
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
    'referenced numeric minimum' => [
        ['$defs' => ['value' => ['type' => 'number', 'minimum' => 1]], '$ref' => '#/properties/target/$defs/value'],
        '0',
    ],
    'enum' => [['type' => 'string', 'enum' => ['draft']], 'other'],
    'projected enum' => [
        ['allOf' => [['type' => 'string', 'enum' => ['draft', 'active']], ['enum' => ['active']]]],
        'draft',
    ],
    'projected minimum length' => [['allOf' => [['type' => 'string'], ['minLength' => 2]]], 'a'],
    'projected maximum length' => [
        ['allOf' => [['type' => 'string', 'maxLength' => 10], ['maxLength' => 3]]],
        'long',
    ],
]);

it('leaves unsupported built-in static types available for connections', function (array $property) {
    $field = FieldMapping::make('mapping')
        ->jsonSchema(
            source: Schema::fromArray(['properties' => ['source' => $property]]),
            target: Schema::fromArray(['properties' => ['target' => $property], 'required' => ['target']]),
        )
        ->withBuiltinStaticForms();
    $schema = FilamentSchema::make(SchemaHost::make())->statePath('data')->components([$field]);
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
        ->jsonSchema(target: Schema::fromArray([
            'properties' => [
                'id' => ['type' => 'string'],
                'active' => ['type' => 'boolean'],
            ],
        ]))
        ->staticForms(fn (string $target, Schema $property) => $target === 'id'
            ? null
            : BuiltinStaticForms::make($property, false));
    FilamentSchema::make(SchemaHost::make())->statePath('data')->components([$field])->fill(['mapping' => []]);

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
            source: Schema::fromArray(['type' => 'object']),
            target: Schema::fromArray([
                'properties' => [
                    'name' => ['type' => 'string', 'title' => 'Name', 'maxLength' => 255],
                    'active' => ['type' => 'boolean', 'title' => 'Active'],
                ],
            ]),
        )
        ->withBuiltinStaticForms();
    $schema = FilamentSchema::make(SchemaHost::make())->statePath('data')->components([$field]);
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

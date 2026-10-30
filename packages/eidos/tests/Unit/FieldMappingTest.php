<?php

use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;
use PHPinnacle\Eidos\Forms\FieldMapping;
use PHPinnacle\Eidos\Forms\JsonSchema;
use PHPinnacle\Eidos\Forms\SchemaCompatibility;
use PHPinnacle\Eidos\Tests\Fixtures\CustomerMappingField;
use PHPinnacle\Eidos\Tests\Fixtures\MappingState;
use Tests\TestCase;

require_once __DIR__ . '/../Fixtures/CustomerMappingField.php';

require_once __DIR__ . '/../Fixtures/MappingState.php';

uses(TestCase::class);

dataset('field mapping mappingValues', [
    'empty mapping' => [[], true],
    'source row must be an array' => [['name'], false],
    'missing kind' => [[['source' => 'name', 'target' => 'title', 'options' => []]], false],
    'disconnected static source' => [
        [[
            'kind' => 'static',
            'target' => null,
            'static' => 'Customer',
            'options' => [],
        ]],
        false,
    ],
    'unknown kind' => [[['kind' => 'other', 'target' => 'title', 'options' => []]], false],
    'unexpected value on source' => [
        [[
            'kind' => 'source',
            'source' => 'name',
            'target' => 'title',
            'options' => [],
            'value' => 'x',
        ]],
        false,
    ],
    'valid mapping' => [MappingState::mapping(['title' => MappingState::source('name')]), true],
    'source reused without permission' => [
        MappingState::mapping(['title' => MappingState::source('name'), 'contact' => MappingState::source('name')]),
        false,
    ],
    'unknown target' => [MappingState::mapping(['missing' => MappingState::source('name')]), false],
    'unknown source' => [MappingState::mapping(['title' => MappingState::source('missing')]), false],
    'numeric source' => [[['kind' => 'source', 'source' => 0, 'target' => 'title', 'options' => []]], false],
    'nested target' => [[['kind' => 'source', 'source' => 'name', 'target' => ['title'], 'options' => []]], false],
    'scalar mapping' => ['name', false],
    'associative mapping' => [['title' => MappingState::source('name')], false],
]);

dataset('field mapping typePairs', [
    'equal' => ['string', 'string', true],
    'different' => ['string', 'boolean', false],
    'untyped source' => [null, 'boolean', true],
    'untyped destination' => ['string', null, true],
    'both untyped' => [null, null, true],
]);

dataset('field mapping orientedMappingValues', [
    'forward multiple sources' => [
        false,
        MappingState::mapping(['payload' => [MappingState::source('id'), MappingState::source('name')]]),
        true,
    ],
    'forward one array item' => [false, MappingState::mapping(['payload' => MappingState::source('id')]), true],
    'forward single destination' => [false, MappingState::mapping(['enabled' => MappingState::source('active')]), true],
    'forward unbound source' => [false, MappingState::mapping([], [], ['id']), true],
    'forward missing target' => [false, [['kind' => 'source', 'source' => 'id', 'options' => []]], false],
    'forward missing options' => [false, [['kind' => 'source', 'source' => 'id', 'target' => 'payload']], false],
    'forward non-array options' => [
        false,
        [['kind' => 'source', 'source' => 'id', 'target' => 'payload', 'options' => 'invalid']],
        false,
    ],
    'forward nested row' => [
        false,
        [[['kind' => 'source', 'source' => 'id', 'target' => 'payload', 'options' => []]]],
        false,
    ],
    'forward numeric source' => [
        false,
        [['kind' => 'source', 'source' => 42, 'target' => 'payload', 'options' => []]],
        false,
    ],
    'forward duplicate within list' => [
        false,
        MappingState::mapping(['payload' => [MappingState::source('id'), MappingState::source('id')]]),
        false,
    ],
    'forward reused source across destinations' => [
        false,
        MappingState::mapping(['payload' => MappingState::source('id'), 'enabled' => MappingState::source('id')]),
        false,
    ],
    'forward unknown source' => [false, MappingState::mapping(['payload' => MappingState::source('unknown')]), false],
    'inverse multiple sources' => [
        true,
        MappingState::mapping(['payload' => [MappingState::source('id'), MappingState::source('name')]]),
        true,
    ],
    'inverse one source' => [true, MappingState::mapping(['payload' => MappingState::source('id')]), true],
    'inverse single destination' => [true, MappingState::mapping(['enabled' => MappingState::source('active')]), true],
    'inverse reused source' => [
        true,
        MappingState::mapping([
            'payload' => MappingState::source('active'),
            'enabled' => MappingState::source('active'),
        ]),
        false,
    ],
    'inverse unbound source' => [true, MappingState::mapping([], [], ['id']), true],
    'inverse numeric source' => [
        true,
        [['kind' => 'source', 'source' => 42, 'target' => 'enabled', 'options' => []]],
        false,
    ],
    'inverse unknown source' => [true, MappingState::mapping(['enabled' => MappingState::source('unknown')]), false],
    'inverse unknown destination' => [true, MappingState::mapping(['unknown' => MappingState::source('id')]), false],
    'inverse scalar state' => [true, 'payload', false],
]);
it('oriented state preserves cardinality and rejects malformed values', function (
    bool $inverse,
    mixed $value,
    bool $valid,
) {
    $field = FieldMapping::make('mapping')
        ->jsonSchema(
            source: ['properties' => ['id' => [], 'name' => [], 'active' => []]],
            dest: [
                'properties' => [
                    'payload' => ['type' => 'array', 'x-mapping' => 'item', 'items' => []],
                    'enabled' => [],
                ],
            ],
        )
        ->inverse($inverse);
    $validator = new Factory(new Translator(new ArrayLoader, 'en'));

    expect(
        $validator
            ->make(['mapping' => $value], ['mapping' => $field->getValidationRules()])
            ->passes(),
    )
        ->toBe($valid);
})->with('field mapping orientedMappingValues');

it('required destinations come from the schema in both orientations', function (bool $inverse) {
    $field = FieldMapping::make('mapping')
        ->jsonSchema(
            source: ['properties' => ['id' => [], 'name' => []]],
            dest: [
                'required' => ['payload'],
                'properties' => ['payload' => ['type' => 'array', 'x-mapping' => 'item'], 'optional' => []],
            ],
        )
        ->inverse($inverse);
    $validator = new Factory(new Translator(new ArrayLoader, 'en'));
    $valid = ['payload' => [MappingState::source('id'), MappingState::source('name')]];
    $missing = MappingState::mapping(['optional' => MappingState::source('id')]);

    expect($field->getRequiredTargets())->toBe(['payload']);
    expect(
        $validator
            ->make(['mapping' => MappingState::mapping($valid)], ['mapping' => $field->getValidationRules()])
            ->passes(),
    )->toBeTrue();
    foreach ([null, [], $missing] as $value) {
        expect(
            $validator
                ->make(['mapping' => $value], ['mapping' => $field->getValidationRules()])
                ->passes(),
        )->toBeFalse();
    }
})->with([false, true]);

it('schema property names retain their identity and metadata independently of titles', function () {
    $field = FieldMapping::make('mapping')->jsonSchema(source: [
        'properties' => [
            '0' => ['title' => 'Name'],
            '00' => ['title' => 'Name'],
            'customer.name' => [],
        ],
    ], dest: CustomerMappingField::jsonSchema());

    expect(array_column($field->getSources(), 'id'))->toBe(['0', '00', 'customer.name']);
    expect(array_column($field->getSources(), 'label'))->toBe(['Name', 'Name', 'customer.name']);
    expect($field->getTargets()[0])->toMatchArray([
        'id' => 'name',
        'label' => 'Customer name',
        'icon' => 'heroicon-o-user',
        'description' => 'The name displayed on the customer profile.',
        'type' => 'string',
        'required' => true,
    ]);
    expect($field->getMultipleTargets())->toBe(['phone']);
});

it('mapping validation rejects unknown identifiers and malformed values', function (mixed $value, bool $valid) {
    $field = FieldMapping::make('mapping')->jsonSchema(
        source: ['properties' => ['name' => [], 'email' => []]],
        dest: ['properties' => ['title' => [], 'contact' => []]],
    );
    $validator = new Factory(new Translator(new ArrayLoader, 'en'));
    expect(
        $validator
            ->make(['mapping' => $value], ['mapping' => $field->getValidationRules()])
            ->passes(),
    )
        ->toBe($valid);
})->with('field mapping mappingValues');

it('schema callbacks change available array destinations reactively', function () {
    $array = true;
    $field = FieldMapping::make('mapping')->jsonSchema(
        source: ['properties' => ['name' => [], 'email' => []]],
        dest: function () use (&$array) {
            return ['properties' => [
                'title' => $array ? ['type' => 'array', 'x-mapping' => 'item'] : ['type' => 'string'],
            ]];
        },
    );
    $validator = new Factory(new Translator(new ArrayLoader, 'en'));
    $state = ['mapping' => MappingState::mapping(['title' => [
        MappingState::source('name'),
        MappingState::source('email'),
    ]])];
    expect($validator->make($state, ['mapping' => $field->getValidationRules()])->passes())->toBeTrue();
    $array = false;
    expect($validator->make($state, ['mapping' => $field->getValidationRules()])->passes())->toBeFalse();
});

it('type compatibility is validated on the server', function (?string $sourceType, ?string $targetType, bool $valid) {
    $source = $sourceType === null ? [] : ['type' => $sourceType];
    $target = $targetType === null ? [] : ['type' => $targetType];
    $validator = new Factory(new Translator(new ArrayLoader, 'en'));
    foreach ([false, true] as $inverse) {
        foreach ([false, true] as $multiple) {
            $field = FieldMapping::make('mapping')
                ->jsonSchema(
                    source: ['properties' => ['source' => $source]],
                    dest: ['properties' => [
                        'target' => $multiple
                            ? ['type' => 'array', 'x-mapping' => 'item', 'items' => $target]
                            : $target,
                    ]],
                )
                ->inverse($inverse);
            $value = ['target' => $multiple ? [MappingState::source('source')] : MappingState::source('source')];
            expect(
                $validator
                    ->make(['mapping' => MappingState::mapping($value)], [
                        'mapping' => $field->getValidationRules(),
                    ])
                    ->passes(),
            )
                ->toBe($valid);
        }
    }
})->with('field mapping typePairs');

it('validates the output schema without changing the source schema', function () {
    $field = FieldMapping::make('mapping')
        ->jsonSchema(
            source: ['properties' => ['active' => ['type' => 'boolean']]],
            dest: ['properties' => ['name' => ['type' => 'string'], 'enabled' => ['type' => 'boolean']]],
        )
        ->outputSchema(fn () => ['properties' => ['active' => ['type' => 'string']]]);
    $validator = new Factory(new Translator(new ArrayLoader, 'en'));
    expect($field->getSources()[0]['type'])->toBe('boolean');
    expect(
        $validator
            ->make(['mapping' => MappingState::mapping(['name' => MappingState::source('active')])], [
                'mapping' => $field->getValidationRules(),
            ])
            ->passes(),
    )->toBeTrue();
    expect(
        $validator
            ->make(['mapping' => MappingState::mapping(['enabled' => MappingState::source('active')])], [
                'mapping' => $field->getValidationRules(),
            ])
            ->passes(),
    )->toBeFalse();
});

it('uses configured schema constraints for connection modes and server validation', function (
    array $source,
    array $target,
    SchemaCompatibility $compatibility,
    bool $allowed,
) {
    $validator = new Factory(new Translator(new ArrayLoader, 'en'));
    foreach ([false, true] as $multiple) {
        $field = FieldMapping::make('mapping')
            ->jsonSchema(
                source: ['properties' => ['source' => ['type' => 'boolean']]],
                dest: ['properties' => [
                    'target' => $multiple ? ['type' => 'array', 'x-mapping' => 'item', 'items' => $target] : $target,
                ]],
            )
            ->outputSchema(fn () => ['properties' => ['source' => $source]])
            ->compatibility($compatibility);
        $value = ['target' => $multiple ? [MappingState::source('source')] : MappingState::source('source')];

        $mode = $multiple ? 'item' : 'whole';
        expect($field->getConnectionModes()['source']['target'] ?? null)->toBe($allowed ? $mode : null);
        expect(
            $validator
                ->make(['mapping' => MappingState::mapping($value)], [
                    'mapping' => $field->getValidationRules(),
                ])
                ->passes(),
        )
            ->toBe($allowed);
    }
})->with([
    'long string rejected' => [
        ['type' => 'string', 'maxLength' => 255],
        ['type' => 'string', 'maxLength' => 64],
        SchemaCompatibility::make(),
        false,
    ],
    'integer to number accepted' => [
        ['type' => 'integer'],
        ['type' => 'number'],
        SchemaCompatibility::make(),
        true,
    ],
    'number to integer rejected' => [
        ['type' => 'number'],
        ['type' => 'integer'],
        SchemaCompatibility::make(),
        false,
    ],
    'short string accepted' => [
        ['type' => 'string', 'maxLength' => 64],
        ['type' => 'string', 'maxLength' => 255],
        SchemaCompatibility::make(),
        true,
    ],
    'length check disabled' => [
        ['type' => 'string', 'maxLength' => 255],
        ['type' => 'string', 'maxLength' => 64],
        SchemaCompatibility::make()->stringLengths(false),
        true,
    ],
    'uuid to plain string' => [
        ['type' => 'string', 'format' => 'uuid'],
        ['type' => 'string'],
        SchemaCompatibility::make(),
        true,
    ],
    'uuid to date rejected' => [
        ['type' => 'string', 'format' => 'uuid'],
        ['type' => 'string', 'format' => 'date'],
        SchemaCompatibility::make(),
        false,
    ],
    'format check disabled' => [
        ['type' => 'string', 'format' => 'uuid'],
        ['type' => 'string', 'format' => 'date'],
        SchemaCompatibility::make()->formats(false),
        true,
    ],
]);

it('adds application restrictions to whole and item connections without overriding schema checks', function () {
    $field = FieldMapping::make('mapping')
        ->jsonSchema(source: ['properties' => ['source' => ['type' => 'string', 'x-domain' => 'customers']]], dest: [
            'properties' => [
                'allowed' => ['type' => 'string', 'x-domain' => 'customers'],
                'blocked' => ['type' => 'string', 'x-domain' => 'orders'],
                'wrong_type' => ['type' => 'boolean', 'x-domain' => 'customers'],
                'items' => [
                    'type' => 'array',
                    'x-mapping' => 'item',
                    'items' => ['type' => 'string', 'x-domain' => 'customers'],
                ],
            ],
        ])
        ->canConnectUsing(
            fn (JsonSchema $source, JsonSchema $target) => (
                ($source->toArray()['x-domain'] ?? null) === ($target->toArray()['x-domain'] ?? null)
            ),
        );
    $validator = new Factory(new Translator(new ArrayLoader, 'en'));

    expect($field->getConnectionModes())->toBe(['source' => ['allowed' => 'whole', 'items' => 'item']]);
    foreach (['allowed' => true, 'blocked' => false, 'wrong_type' => false] as $target => $allowed) {
        expect(
            $validator
                ->make(['mapping' => MappingState::mapping([
                    $target => MappingState::source('source'),
                ])], ['mapping' => $field->getValidationRules()])
                ->passes(),
        )
            ->toBe($allowed);
    }
});

it('passes literal field names to connection restrictions for whole and item mappings', function () {
    $field = FieldMapping::make('mapping')
        ->jsonSchema(
            source: [
                'properties' => [
                    'record.id' => ['type' => 'string'],
                    'other.id' => ['type' => 'string'],
                ],
            ],
            dest: [
                'properties' => [
                    'key.id' => ['type' => 'string'],
                    'label' => ['type' => 'string'],
                    'key.parts' => ['type' => 'array', 'x-mapping' => 'item', 'items' => ['type' => 'string']],
                ],
            ],
        )
        ->canConnectUsing(
            fn (JsonSchema $source, JsonSchema $target, string $sourceName, string $targetName) => (
                $targetName === 'label'
                || $sourceName === 'record.id'
            ),
        );
    $validator = new Factory(new Translator(new ArrayLoader, 'en'));

    expect($field->getConnectionModes())->toBe([
        'record.id' => ['key.id' => 'whole', 'label' => 'whole', 'key.parts' => 'item'],
        'other.id' => ['label' => 'whole'],
    ]);
    foreach (['record.id' => true, 'other.id' => false] as $source => $allowed) {
        foreach ([
            'key.id' => MappingState::source($source),
            'key.parts' => [MappingState::source($source)],
        ] as $target => $entry) {
            expect(
                $validator
                    ->make(
                        ['mapping' => MappingState::mapping([$target => $entry])],
                        ['mapping' => $field->getValidationRules()],
                    )
                    ->passes(),
            )
                ->toBe($allowed);
        }
    }
});

it('distinguishes whole arrays from explicitly collected items', function (bool $inverse) {
    $field = FieldMapping::make('mapping')
        ->jsonSchema(
            source: [
                'properties' => [
                    'list' => ['type' => 'array', 'items' => ['type' => 'string']],
                    'name' => ['type' => 'string'],
                    'email' => ['type' => 'string'],
                ],
            ],
            dest: [
                'required' => ['contacts'],
                'properties' => [
                    'contacts' => ['type' => 'array', 'x-mapping' => 'item', 'items' => ['type' => 'string']],
                    'whole_contacts' => ['type' => 'array', 'items' => ['type' => 'string']],
                ],
            ],
        )
        ->inverse($inverse);
    $validator = new Factory(new Translator(new ArrayLoader, 'en'));
    $values = [
        [['contacts' => MappingState::source('list')], false],
        [['whole_contacts' => MappingState::source('list'), 'contacts' => MappingState::source('name')], true],
        [['contacts' => [MappingState::source('name'), MappingState::source('email')]], true],
        [['contacts' => [MappingState::source('list'), MappingState::source('name')]], false],
    ];
    foreach ($values as [$value, $valid]) {
        expect(
            $validator
                ->make(['mapping' => MappingState::mapping($value)], ['mapping' => $field->getValidationRules()])
                ->passes(),
        )
            ->toBe($valid);
    }
})->with([false, true]);

it('accepts array sources as items only when the destination items are arrays', function () {
    $field = FieldMapping::make('mapping')->jsonSchema(
        source: ['properties' => ['list' => ['type' => 'array', 'items' => ['type' => 'string']]]],
        dest: [
            'properties' => [
                'matrix' => [
                    'type' => 'array',
                    'x-mapping' => 'item',
                    'items' => ['type' => 'array', 'items' => ['type' => 'string']],
                ],
            ],
        ],
    );
    $validator = new Factory(new Translator(new ArrayLoader, 'en'));
    expect(
        $validator
            ->make(['mapping' => MappingState::mapping(['matrix' => MappingState::source('list')])], [
                'mapping' => $field->getValidationRules(),
            ])
            ->passes(),
    )->toBeTrue();
});

it('static values are accepted for every known destination and retain string form validation', function (
    mixed $value,
    bool $valid,
) {
    $field = FieldMapping::make('mapping')
        ->jsonSchema(dest: ['required' => ['enabled'], 'properties' => ['enabled' => ['type' => 'boolean']]]);
    $validator = new Factory(new Translator(new ArrayLoader, 'en'));
    expect(
        $validator
            ->make(['mapping' => $value], ['mapping' => $field->getValidationRules()])
            ->passes(),
    )
        ->toBe($valid);
})->with([
    'string boolean' => [MappingState::mapping(['enabled' => MappingState::value('true')]), true],
    'empty value' => [MappingState::mapping(['enabled' => MappingState::value('')]), false],
    'native boolean' => [MappingState::mapping(['enabled' => MappingState::value(true)]), true],
    'missing static value' => [[['kind' => 'static', 'target' => 'enabled', 'options' => []]], false],
    'unexpected type on static' => [
        [[
            'kind' => 'static',
            'target' => 'enabled',
            'static' => 'true',
            'options' => [],
            'type' => 'boolean',
        ]],
        false,
    ],
    'unknown destination' => [MappingState::mapping(['other' => MappingState::value('true')]), false],
]);

it('rejects source settings for identifiers outside the source schema', function () {
    $field = FieldMapping::make('mapping')->jsonSchema(source: ['properties' => ['name' => []]]);
    $validator = new Factory(new Translator(new ArrayLoader, 'en'));

    expect(
        $validator
            ->make(['mapping' => MappingState::mapping([], ['missing' => ['alias' => 'Unknown']])], [
                'mapping' => $field->getValidationRules(),
            ])
            ->passes(),
    )->toBeFalse();
});

it('uses enum compatibility for connection availability and submitted mappings in both orientations', function (bool $inverse) {
    $field = FieldMapping::make('mapping')
        ->inverse($inverse)
        ->jsonSchema(
            source: [
                'properties' => [
                    'date' => ['type' => 'string', 'format' => 'date'],
                    'status' => ['type' => 'string', 'enum' => ['draft', 'active']],
                    'narrow' => ['type' => 'string', 'enum' => ['active']],
                    'wide' => ['type' => 'string', 'enum' => ['draft', 'active', 'blocked']],
                ],
            ],
            dest: ['properties' => ['status' => ['type' => 'string', 'enum' => ['draft', 'active']]]],
        );
    $modes = $field->getConnectionModes();
    $validator = new Factory(new Translator(new ArrayLoader, 'en'));
    foreach (['date' => false, 'status' => true, 'narrow' => true, 'wide' => false] as $source => $allowed) {
        expect($modes[$source]['status'] ?? null)->toBe($allowed ? 'whole' : null);
        expect(
            $validator
                ->make(
                    ['mapping' => MappingState::mapping(['status' => MappingState::source($source)])],
                    ['mapping' => $field->getValidationRules()],
                )
                ->passes(),
        )
            ->toBe($allowed);
    }
})->with([false, true]);

it('keeps unannotated arrays and objects as whole values', function () {
    $field = FieldMapping::make('mapping')->jsonSchema(
        source: [
            'properties' => [
                'text' => ['type' => 'string'],
                'list' => ['type' => 'array', 'items' => ['type' => 'string']],
                'object' => ['type' => 'object'],
            ],
        ],
        dest: [
            'properties' => [
                'list' => ['type' => 'array', 'items' => ['type' => 'string']],
                'object' => ['type' => 'object'],
            ],
        ],
    );

    expect($field->getMultipleTargets())->toBe([]);
    expect($field->getConnectionModes())->toBe([
        'list' => ['list' => 'whole'],
        'object' => ['object' => 'whole'],
    ]);
});

it('collects items of each supported type using the item schema', function (
    array $source,
    array $items,
    bool $allowed,
) {
    $field = FieldMapping::make('mapping')->jsonSchema(
        source: ['properties' => ['field' => $source]],
        dest: ['properties' => ['collection' => ['type' => 'array', 'x-mapping' => 'item', 'items' => $items]]],
    );
    $validator = new Factory(new Translator(new ArrayLoader, 'en'));

    expect($field->getConnectionModes()['field']['collection'] ?? null)->toBe($allowed ? 'item' : null);
    expect(
        $validator
            ->make(
                ['mapping' => MappingState::mapping(['collection' => MappingState::source('field')])],
                ['mapping' => $field->getValidationRules()],
            )
            ->passes(),
    )
        ->toBe($allowed);
})->with([
    'string in union' => [['type' => 'string'], ['type' => ['string', 'integer', 'boolean']], true],
    'integer in union' => [['type' => 'integer'], ['type' => ['string', 'integer', 'boolean']], true],
    'boolean in union' => [['type' => 'boolean'], ['type' => ['string', 'integer', 'boolean']], true],
    'number outside union' => [['type' => 'number'], ['type' => ['string', 'integer', 'boolean']], false],
    'nullable integer' => [['type' => ['integer', 'null']], ['type' => ['number', 'null']], true],
    'null' => [['type' => 'null'], ['type' => ['string', 'null']], true],
    'object' => [['type' => 'object'], ['type' => 'object'], true],
    'nested array' => [
        ['type' => 'array', 'items' => ['type' => 'string']],
        ['type' => 'array', 'items' => ['type' => 'string']],
        true,
    ],
    'unrestricted item' => [['type' => 'array'], [], true],
]);

it('collects object properties under literal source names in both orientations', function (bool $inverse) {
    $field = FieldMapping::make('mapping')
        ->inverse($inverse)
        ->jsonSchema(
            source: [
                'properties' => [
                    '_my_field' => ['type' => 'integer'],
                    'customer.name' => ['type' => 'string'],
                    '0' => ['type' => 'boolean'],
                    'details' => ['type' => 'object'],
                    'tags' => ['type' => 'array'],
                ],
            ],
            dest: [
                'required' => ['custom'],
                'properties' => ['custom' => ['type' => 'object', 'x-mapping' => 'property']],
            ],
        );
    $state = MappingState::mapping(['custom' => [
        MappingState::source('_my_field'),
        MappingState::source('customer.name'),
        MappingState::source('0'),
        MappingState::source('details'),
        MappingState::source('tags'),
    ]]);
    $validator = new Factory(new Translator(new ArrayLoader, 'en'));

    expect($field->getMultipleTargets())->toBe(['custom']);
    expect($field->getConnectionModes())->toBe([
        '_my_field' => ['custom' => 'property'],
        'customer.name' => ['custom' => 'property'],
        0 => ['custom' => 'property'],
        'details' => ['custom' => 'property'],
        'tags' => ['custom' => 'property'],
    ]);
    expect($validator->make(['mapping' => $state], ['mapping' => $field->getValidationRules()])->passes())->toBeTrue();
    expect($validator->make(['mapping' => []], ['mapping' => $field->getValidationRules()])->passes())->toBeFalse();
})->with([false, true]);

it('checks property output schemas and application restrictions without renaming source keys', function () {
    $field = FieldMapping::make('mapping')
        ->jsonSchema(
            source: [
                'properties' => [
                    'name' => ['type' => 'boolean'],
                    'enabled' => ['type' => 'boolean'],
                    'blocked' => ['type' => 'string'],
                    'unknown' => ['type' => 'string'],
                ],
            ],
            dest: [
                'properties' => [
                    'custom' => [
                        'type' => 'object',
                        'x-mapping' => 'property',
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'enabled' => ['type' => 'string'],
                            'blocked' => [],
                        ],
                        'additionalProperties' => false,
                    ],
                ],
            ],
        )
        ->outputSchema(fn () => [
            'properties' => [
                'name' => ['type' => 'string'],
                'enabled' => ['type' => 'boolean'],
                'blocked' => ['type' => 'string'],
                'unknown' => ['type' => 'string'],
            ],
        ])
        ->canConnectUsing(
            fn (JsonSchema $source, JsonSchema $target, string $sourceName, string $targetName) => (
                $sourceName !== 'blocked'
                && $targetName === 'custom'
            ),
        );
    $validator = new Factory(new Translator(new ArrayLoader, 'en'));

    expect($field->getConnectionModes())->toBe(['name' => ['custom' => 'property']]);
    foreach (['name' => true, 'enabled' => false, 'blocked' => false, 'unknown' => false] as $source => $allowed) {
        expect(
            $validator
                ->make(
                    ['mapping' => MappingState::mapping(['custom' => MappingState::source($source)])],
                    ['mapping' => $field->getValidationRules()],
                )
                ->passes(),
        )
            ->toBe($allowed);
    }
});

it('uses the additional property schema for unnamed object properties', function (
    array|bool $additional,
    bool $allowed,
) {
    $field = FieldMapping::make('mapping')->jsonSchema(
        source: ['properties' => ['count' => ['type' => 'integer']]],
        dest: [
            'properties' => ['custom' => [
                'type' => 'object',
                'x-mapping' => 'property',
                'additionalProperties' => $additional,
            ]],
        ],
    );

    expect($field->getConnectionModes()['count']['custom'] ?? null)->toBe($allowed ? 'property' : null);
})->with([
    'open' => [true, true],
    'closed' => [false, false],
    'numeric' => [['type' => 'number'], true],
    'text' => [['type' => 'string'], false],
]);

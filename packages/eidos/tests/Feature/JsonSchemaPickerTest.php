<?php

use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use PHPinnacle\Eidos\Forms\JsonSchemaPicker;
use PHPinnacle\Eidos\Infolists\JsonSchemaEntry;
use Tests\TestCase;

uses(TestCase::class);

it('offers clearing only for optional enabled pickers independently of schema required markers', function () {
    [$form] = eidos_json_schema_form(['totals', 'subtotal']);
    $picker = $form->getComponents()[0]->requiredMarker();
    view()->share('errors', new ViewErrorBag);

    expect($picker->toHtml())->toContain('aria-label="Clear selection"');
    expect($form->getState()['selection'])->toBe(['totals', 'subtotal']);

    $required = true;
    $picker->required(function () use (&$required) {
        return $required;
    });
    expect($picker->toHtml())->not->toContain('aria-label="Clear selection"');

    $required = false;
    expect($picker->toHtml())->toContain('aria-label="Clear selection"');

    $picker->disabled();
    expect($picker->toHtml())->not->toContain('aria-label="Clear selection"');
});

it('uses Laravel schema objects and closures for selection and readonly rendering', function (bool $useClosure) {
    $selection = ['customer', 'email'];
    [$form] = eidos_json_schema_form($selection);
    $schema = JsonSchema::anyOf([
        JsonSchema::object([
            'customer' => JsonSchema::object([
                'email' => JsonSchema::string()->nullable()->description('Laravel schema address'),
            ]),
            'tags' => JsonSchema::array()->items(JsonSchema::object([
                'name' => JsonSchema::string(),
            ])),
        ]),
        JsonSchema::object(['count' => JsonSchema::integer()]),
    ]);
    $picker = $form->getComponents()[0];
    $entry = $form->getComponents()[1];
    $picker->jsonSchema($useClosure ? fn () => $schema : $schema);
    $entry->jsonSchema($useClosure ? fn () => $schema : $schema);
    view()->share('errors', new ViewErrorBag);

    expect($form->getState()['selection'])->toBe($selection);
    expect($picker->toHtml())->toContain('Laravel schema address');
    expect($entry->toHtml())->toContain('Laravel schema address');

    $form->fill(['selection' => ['count']]);

    expect($form->getState()['selection'])->toBe(['count']);
    expect($entry->getState())->toBe(['count']);
})->with(['direct' => false, 'closure' => true]);

it('offers a schema inspector in regular mode and preserves the path when switching to inline mode', function (
    ?array $path,
) {
    [$form] = eidos_json_schema_form($path);
    $entry = $form->getComponents()[1];
    view()->share('errors', new ViewErrorBag);

    expect($entry->toHtml())->toContain('aria-label="View full schema"');
    expect($entry->getState())->toBe($path);

    $inline = true;
    $entry->inline(function () use (&$inline) {
        return $inline;
    });

    expect($entry->toHtml())->not->toContain('aria-label="View full schema"');
    expect($entry->getState())->toBe($path);

    $inline = false;

    expect($entry->toHtml())->toContain('aria-label="View full schema"');
    expect($entry->getState())->toBe($path);
})->with([
    'nested path' => [['customer', 'email']],
    'literal names' => [['literal.key', 'slash/key']],
    'no path' => [null],
]);

it('validates exact union paths against the current schema closure', function () {
    $selection = ['данные/客户', 'email'];
    [$form] = eidos_json_schema_form($selection);
    $schema = JsonSchema::anyOf([
        JsonSchema::object(['данные/客户' => JsonSchema::object(['email' => JsonSchema::string()])]),
        JsonSchema::object(['данные/客户' => JsonSchema::object(['email' => JsonSchema::integer()->nullable()])]),
    ]);
    $picker = $form->getComponents()[0];
    $picker->jsonSchema(function () use (&$schema) {
        return $schema;
    });

    expect($form->getState()['selection'])->toBe($selection);

    $schema = JsonSchema::object(['count' => JsonSchema::integer()]);

    expect($form->getState(...))->toThrow(ValidationException::class);
});

it('keeps nested composition paths within one complete branch', function (
    array $selection,
    bool $valid,
    string $keyword,
) {
    [$form] = eidos_json_schema_form($selection);
    $form->getComponents()[0]->jsonSchema(\PHPinnacle\Eidos\JsonSchema\Schema::fromArray([
        $keyword => [
            [
                'type' => 'object',
                'properties' => [
                    'payload' => [
                        $keyword => [
                            [
                                'type' => 'object',
                                'properties' => [
                                    'metadata' => [
                                        'type' => 'object',
                                        'properties' => ['version' => ['type' => 'integer']],
                                    ],
                                ],
                            ],
                            [
                                'type' => 'object',
                                'properties' => [
                                    'contact' => [
                                        'type' => 'object',
                                        'properties' => ['email' => ['type' => 'string']],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'object',
                'properties' => ['payload' => ['type' => 'object', 'properties' => ['tags' => ['type' => 'array']]]],
            ],
        ],
    ]));

    if ($valid) {
        expect($form->getState()['selection'])->toBe($selection);
    } else {
        expect($form->getState(...))->toThrow(ValidationException::class);
    }
})->with([
    'nested union property' => [['payload', 'metadata', 'version'], true],
    'other nested variant' => [['payload', 'contact', 'email'], true],
    'other root variant' => [['payload', 'tags'], true],
    'crossed variants' => [['payload', 'metadata', 'email'], false],
    'array item' => [['payload', 'tags', 'name'], false],
])->with(['anyOf', 'oneOf', 'allOf']);

it('accepts exact schema path segments', function (array $selection) {
    [$form] = eidos_json_schema_form($selection);

    expect($form->getState()['selection'])->toBe($selection);
})->with([
    'nested' => [['customer', 'email']],
    'literal key' => [['literal.key', 'slash/key']],
    'numeric key' => [['01']],
    'array field' => [['tags']],
    'subtotal' => [['totals', 'subtotal']],
]);

it('defaults to an absent selection', function () {
    [$form] = eidos_json_schema_form(null);
    $form->fill();

    expect($form->getState()['selection'])->toBeNull();
});

it('does not confuse literal dotted names with nested schema paths', function (bool $nested) {
    $selection = $nested ? ['metadata', 'version'] : ['metadata.version'];
    [$form] = eidos_json_schema_form($selection);
    $form->getComponents()[0]->jsonSchema(JsonSchema::object(
        $nested
            ? ['metadata' => JsonSchema::object(['version' => JsonSchema::integer()])]
            : ['metadata.version' => JsonSchema::integer()],
    ));

    expect($form->getState()['selection'])->toBe($selection);

    $form->fill(['selection' => $nested ? ['metadata.version'] : ['metadata', 'version']]);

    expect($form->getState(...))->toThrow(ValidationException::class);
})->with(['literal name' => false, 'nested property' => true]);

it('allows consumers to format submitted paths with native Filament dehydration', function (?array $selection) {
    [$form] = eidos_json_schema_form($selection);
    $picker = $form->getComponents()[0]
        ->dehydrateStateUsing(fn (?array $state) => $state === null ? null : implode('.', $state));

    expect($form->getState()['selection'])->toBe($selection === null ? null : 'totals.subtotal');
    expect($picker->getState())->toBe($selection);
})->with(['selected' => [['totals', 'subtotal']], 'absent' => [null]]);

it('accepts a field path for a required picker', function () {
    [$form] = eidos_json_schema_form(['customer', 'email']);
    $form->getComponents()[0]->required();

    expect($form->getState()['selection'])->toBe(['customer', 'email']);
});

it('rejects absent and empty selections for a required picker', function (mixed $selection) {
    [$form] = eidos_json_schema_form($selection);
    $form->getComponents()[0]->required();

    expect($form->getState(...))->toThrow(ValidationException::class);
})->with(['absent' => [null], 'empty path' => [[]]]);

it('accepts an absent selection for an optional picker', function () {
    [$form] = eidos_json_schema_form(null);

    expect($form->getState()['selection'])->toBeNull();
});

it('rejects malformed, missing and array item selections', function (mixed $selection) {
    [$form] = eidos_json_schema_form($selection);

    expect($form->getState(...))->toThrow(ValidationException::class);
})->with([
    'empty path' => [[]],
    'string path' => ['customer.email'],
    'number' => [42],
    'boolean' => [false],
    'empty string' => '',
    'whitespace' => ' ',
    'encoded path' => '["customer","email"]',
    'missing' => [['missing']],
    'array item' => [['tags', 'name']],
    'non-string segment' => [['customer', 1]],
    'nested array' => [[['customer', 'email']]],
    'associative array' => [['field' => 'customer']],
    'sparse list' => [[0 => 'customer', 2 => 'email']],
    'different numeric key' => [['1']],
]);

it('renders schema descriptions in the picker and readonly infolist entry', function () {
    [$form] = eidos_json_schema_form(['customer', 'email']);
    view()->share('errors', new ViewErrorBag);

    $picker = $form->getComponents()[0]->toHtml();
    $entry = $form->getComponents()[1]->toHtml();

    expect($picker)->toContain('Delivery address');
    expect($entry)->toContain('Delivery address');
});

/** @return array{Schema, Component} */
function eidos_json_schema_form(mixed $selection): array
{
    $livewire = new class extends Component implements HasSchemas {
        use InteractsWithSchemas;

        /** @var array<string, mixed> */
        public array $data = [];

        public function form(Schema $schema): Schema
        {
            $jsonSchema = JsonSchema::object([
                'customer' => JsonSchema::object([
                    'email' => JsonSchema::string()->nullable()->description('Delivery address'),
                ]),
                'totals' => JsonSchema::object(['subtotal' => JsonSchema::number()]),
                'literal.key' => JsonSchema::object(['slash/key' => JsonSchema::boolean()]),
                'tags' => JsonSchema::array()->items(JsonSchema::object(['name' => JsonSchema::string()])),
                '01' => JsonSchema::string(),
            ]);

            return $schema
                ->statePath('data')
                ->components([
                    JsonSchemaPicker::make('selection')->jsonSchema($jsonSchema),
                    JsonSchemaEntry::make('preview')
                        ->jsonSchema($jsonSchema)
                        ->state(fn (Get $get) => $get('selection')),
                ]);
        }
    };
    $livewire->setId('eidos-json-schema-test');
    $livewire->setName('eidos-json-schema-test');
    $form = $livewire->getSchema('form');
    $form->fill(['selection' => $selection]);

    return [$form, $livewire];
}

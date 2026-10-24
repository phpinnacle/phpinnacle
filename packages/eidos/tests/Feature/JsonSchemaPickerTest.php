<?php

use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use PHPinnacle\Eidos\Forms\JsonSchemaPicker;
use PHPinnacle\Eidos\Infolists\JsonSchemaEntry;
use Tests\TestCase;

uses(TestCase::class);

it('selects array item fields only when explicitly enabled and preserves their JSONPath state', function () {
    $path = "$['tags'][*]['name']";
    $form = eidos_json_schema_form($path);
    $picker = $form->getComponent('selection');
    $entry = $form->getComponent('preview');
    view()->share('errors', new ViewErrorBag);

    expect($form->getState(...))->toThrow(ValidationException::class);

    $enabled = true;
    $picker->selectArrayItemFields(function () use (&$enabled) {
        return $enabled;
    });
    expect($form->getState()['selection'])
        ->toBe($path)
        ->and($entry->getState())
        ->toBe($path)
        ->and($picker->toHtml())
        ->toContain('schemaSelectArrayItemFields = true');

    $enabled = false;
    expect($picker->getState())->toBe($path);
    expect($form->getState(...))->toThrow(ValidationException::class);

    $form->fill(['selection' => "$['tags']"]);
    expect($form->getState()['selection'])->toBe("$['tags']");
});

it('validates and presents exact paths in schema types that preserve local references', function () {
    $form = eidos_json_schema_form("$['home_address']['postal_code']");
    $schema = new class extends Type {
        public function toArray(): array
        {
            return [
                'type' => 'object',
                '$defs' => [
                    'address' => [
                        'type' => 'object',
                        'properties' => ['postal_code' => ['type' => 'string', 'x-icon' => 'heroicon-o-map-pin']],
                    ],
                ],
                'properties' => ['home_address' => ['$ref' => '#/$defs/address']],
            ];
        }
    };
    $picker = $form->getComponent('selection')->jsonSchema($schema);
    $entry = $form->getComponent('preview')->jsonSchema($schema);

    expect($form->getState()['selection'])
        ->toBe("$['home_address']['postal_code']")
        ->and($picker->getExtraViewData()['icons'])
        ->toHaveKey('heroicon-o-map-pin')
        ->and($entry->getExtraViewData()['propertyTitles'])
        ->toHaveKey('postal_code');

    $form->fill(['selection' => "$['home_address']['missing']"]);

    expect($form->getState(...))->toThrow(ValidationException::class);
});

it('offers clearing only for optional enabled pickers independently of schema required markers', function () {
    $form = eidos_json_schema_form("$['totals']['subtotal']");
    $picker = $form->getComponent('selection')->requiredMarker();
    view()->share('errors', new ViewErrorBag);

    expect($picker->toHtml())->toContain('aria-label="Clear selection"');
    expect($form->getState()['selection'])->toBe("$['totals']['subtotal']");

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
    $selection = "$['customer']['email']";
    $form = eidos_json_schema_form($selection);
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
    $picker = $form->getComponent('selection');
    $entry = $form->getComponent('preview');
    $picker->jsonSchema($useClosure ? fn () => $schema : $schema);
    $entry->jsonSchema($useClosure ? fn () => $schema : $schema);
    view()->share('errors', new ViewErrorBag);

    expect($form->getState()['selection'])->toBe($selection);
    expect($picker->toHtml())->toContain('Laravel schema address');
    expect($entry->toHtml())->toContain('Laravel schema address');

    $form->fill(['selection' => "$['count']"]);

    expect($form->getState()['selection'])->toBe("$['count']");
    expect($entry->getState())->toBe("$['count']");
})->with(['direct' => false, 'closure' => true]);

it('offers a schema inspector in regular mode and preserves the path when switching to inline mode', function (
    ?string $path,
) {
    $form = eidos_json_schema_form($path);
    $entry = $form->getComponent('preview');
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
    'nested path' => ["$['customer']['email']"],
    'literal names' => ["$['literal.key']['slash/key']"],
    'no path' => [null],
]);

it('validates exact union paths against the current schema closure', function () {
    $selection = "$['данные/客户']['email']";
    $form = eidos_json_schema_form($selection);
    $schema = JsonSchema::anyOf([
        JsonSchema::object(['данные/客户' => JsonSchema::object(['email' => JsonSchema::string()])]),
        JsonSchema::object(['данные/客户' => JsonSchema::object(['email' => JsonSchema::integer()->nullable()])]),
    ]);
    $picker = $form->getComponent('selection');
    $picker->jsonSchema(function () use (&$schema) {
        return $schema;
    });

    expect($form->getState()['selection'])->toBe($selection);

    $schema = JsonSchema::object(['count' => JsonSchema::integer()]);

    expect($form->getState(...))->toThrow(ValidationException::class);
});

it('keeps nested composition paths within one complete branch', function (
    string $selection,
    bool $valid,
    string $keyword,
) {
    $form = eidos_json_schema_form($selection);
    $form->getComponent('selection')->jsonSchema(\PHPinnacle\Eidos\JsonSchema\Schema::fromArray([
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
    'nested union property' => ["$['payload']['metadata']['version']", true],
    'other nested variant' => ["$['payload']['contact']['email']", true],
    'other root variant' => ["$['payload']['tags']", true],
    'crossed variants' => ["$['payload']['metadata']['email']", false],
    'array item' => ["$['payload']['tags']['name']", false],
])->with(['anyOf', 'oneOf', 'allOf']);

it('accepts exact schema path segments', function (string $selection) {
    $form = eidos_json_schema_form($selection);

    expect($form->getState()['selection'])->toBe($selection);
})->with([
    'nested' => ["$['customer']['email']"],
    'literal key' => ["$['literal.key']['slash/key']"],
    'numeric key' => ["$['01']"],
    'array field' => ["$['tags']"],
    'subtotal' => ["$['totals']['subtotal']"],
]);

it('defaults to an absent selection', function () {
    $form = eidos_json_schema_form(null);
    $form->fill();

    expect($form->getState()['selection'])->toBeNull();
});

it('does not confuse literal dotted names with nested schema paths', function (bool $nested) {
    $selection = $nested ? "$['metadata']['version']" : "$['metadata.version']";
    $form = eidos_json_schema_form($selection);
    $form->getComponent('selection')->jsonSchema(JsonSchema::object(
        $nested
            ? ['metadata' => JsonSchema::object(['version' => JsonSchema::integer()])]
            : ['metadata.version' => JsonSchema::integer()],
    ));

    expect($form->getState()['selection'])->toBe($selection);

    $form->fill(['selection' => $nested ? "$['metadata.version']" : "$['metadata']['version']"]);

    expect($form->getState(...))->toThrow(ValidationException::class);
})->with(['literal name' => false, 'nested property' => true]);

it('allows consumers to format submitted paths with native Filament dehydration', function (?string $selection) {
    $form = eidos_json_schema_form($selection);
    $picker = $form->getComponent('selection')
        ->dehydrateStateUsing(fn (?string $state) => $state === null ? null : 'selected:' . $state);

    expect($form->getState()['selection'])->toBe($selection === null ? null : 'selected:' . $selection);
    expect($picker->getState())->toBe($selection);
})->with(['selected' => ["$['totals']['subtotal']"], 'absent' => [null]]);

it('accepts a field path for a required picker', function () {
    $form = eidos_json_schema_form("$['customer']['email']");
    $form->getComponent('selection')->required();

    expect($form->getState()['selection'])->toBe("$['customer']['email']");
});

it('rejects absent and empty selections for a required picker', function (mixed $selection) {
    $form = eidos_json_schema_form($selection);
    $form->getComponent('selection')->required();

    expect($form->getState(...))->toThrow(ValidationException::class);
})->with(['absent' => [null], 'empty path' => [[]]]);

it('accepts an absent selection for an optional picker', function () {
    $form = eidos_json_schema_form(null);

    expect($form->getState()['selection'])->toBeNull();
});

it('rejects malformed, missing and array item selections', function (mixed $selection) {
    $form = eidos_json_schema_form($selection);

    expect($form->getState(...))->toThrow(ValidationException::class);
})->with([
    'empty path' => [[]],
    'dotted path' => ['customer.email'],
    'root' => ['$'],
    'legacy array path' => [['customer', 'email']],
    'number' => [42],
    'boolean' => [false],
    'empty string' => '',
    'whitespace' => ' ',
    'encoded path' => '["customer","email"]',
    'missing' => ["$['missing']"],
    'array item' => ["$['tags']['name']"],
    'non-string segment' => [['customer', 1]],
    'nested array' => [["$['customer']['email']"]],
    'associative array' => [['field' => 'customer']],
    'sparse list' => [[0 => 'customer', 2 => 'email']],
    'different numeric key' => ["$['1']"],
]);

it('prepares dropdown trees before opening so their full height can be measured', function () {
    $form = eidos_json_schema_form(null);
    view()->share('errors', new ViewErrorBag);

    foreach ($form->getComponents() as $component) {
        $document = new DOMDocument;
        $document->loadHTML($component->toHtml(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($document);

        expect($xpath->query(
            '//div[contains(@class, "fi-dropdown-panel")]//div[contains(@class, "eidos-json-schema-tree") and not(ancestor::template)]',
        )->length)
            ->toBeGreaterThan(0);
    }
});

it('renders schema descriptions in the picker and readonly infolist entry', function () {
    $form = eidos_json_schema_form("$['customer']['email']");
    view()->share('errors', new ViewErrorBag);

    $picker = $form->getComponent('selection')->toHtml();
    $entry = $form->getComponent('preview')->toHtml();

    expect($picker)->toContain('Delivery address');
    expect($entry)->toContain('Delivery address');
});

function eidos_json_schema_form(mixed $selection): Schema
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

    return $form;
}

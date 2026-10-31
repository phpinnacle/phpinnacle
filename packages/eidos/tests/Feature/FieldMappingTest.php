<?php

use Filament\Actions\Action;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Schema as FilamentSchema;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Js;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PHPinnacle\Eidos\Enums\MappingMode;
use PHPinnacle\Eidos\Forms\FieldBinding;
use PHPinnacle\Eidos\Forms\FieldMapping;
use PHPinnacle\Eidos\Tests\Fixtures\MappingState;
use PHPinnacle\Eidos\Tests\Fixtures\SchemaHost;
use PHPinnacle\Jason\Schema;
use Tests\TestCase;

require_once __DIR__ . '/../Fixtures/MappingState.php';

require_once __DIR__ . '/../Fixtures/SchemaHost.php';
require_once __DIR__ . '/../Fixtures/FieldMappingForm.php';

uses(TestCase::class);

it('hydrates composed source schemas with native settings', function (bool $binding, bool $dynamic) {
    $source = Schema::fromArray([
        '$defs' => ['fields' => ['properties' => ['name' => ['type' => 'string']]]],
        'allOf' => [['$ref' => '#/$defs/fields']],
    ]);
    $configured = $dynamic ? fn () => $source : $source;
    $field = $binding
        ? FieldBinding::make('mapping')->jsonSchema($configured)->simple(TextInput::make('destination'))
        : FieldMapping::make('mapping')->jsonSchema(source: $configured);
    $field->sourceSettings([TextInput::make('alias')->default('Display name')]);
    $schema = FilamentSchema::make(SchemaHost::make())->statePath('data')->components([$field]);

    $schema->fill();

    expect($field->getSourceSchema())->toBe($source);
    expect($schema->getState()['mapping'])->toBe([
        ['kind' => 'source', 'source' => 'name', 'target' => null, 'options' => ['alias' => 'Display name']],
    ]);
})->with([false, true])->with([false, true]);

it('blocks mapping editor interaction during Livewire requests', function (string $editor) {
    $source = ['properties' => ['name' => ['type' => 'string']]];
    $field = $editor === 'mapping'
        ? FieldMapping::make('editor')->jsonSchema(
            source: Schema::fromArray($source),
            target: Schema::fromArray($source),
        )
        : FieldBinding::make('editor')
            ->jsonSchema(Schema::fromArray($source))
            ->simple(TextInput::make('destination'));
    $schema = FilamentSchema::make(SchemaHost::make())->statePath('data')->components([$field]);
    $schema->fill();
    view()->share('errors', new ViewErrorBag);

    $document = new DOMDocument;
    $document->loadHTML($schema->toHtml(), LIBXML_NOERROR | LIBXML_NOWARNING);
    $nodes = new DOMXPath($document)->query(
        '//*[@*[name()="wire:loading.class" and .="eidos-mapping-loading"]]',
    );

    expect($nodes->length)->toBe(1);
    expect($nodes->item(0)->getAttribute('wire:loading.attr'))->toBe('inert');
    if ($editor === 'mapping') {
        expect($nodes->item(0)->getAttribute('x-on:keydown.escape.window'))->toBe('cancel()');
    }
})->with(['mapping', 'binding']);

it('defers mapping changes with native static forms and source settings', function () {
    $field = FieldMapping::make('mapping')
        ->jsonSchema(
            source: Schema::fromArray(['properties' => ['name' => ['type' => 'string']]]),
            target: Schema::fromArray(['properties' => ['name' => ['type' => 'string']]]),
        )
        ->staticForms(fn () => TextInput::make('value'))
        ->sourceSettings([TextInput::make('alias')]);
    $schema = FilamentSchema::make(SchemaHost::make())->statePath('data')->components([$field]);
    $schema->fill();
    view()->share('errors', new ViewErrorBag);

    expect(html_entity_decode($schema->toHtml()))
        ->toContain("state: \$wire.\$entangle('data.mapping', false),")
        ->not->toContain("\$entangle('data.mapping').live");
});

it('keeps native editor addresses and submits ordered connections without inactive static drafts', function () {
    $field = FieldMapping::make('mapping')
        ->jsonSchema(
            source: Schema::fromArray([
                'properties' => ['first' => ['type' => 'string'], 'second' => ['type' => 'string']],
            ]),
            target: Schema::fromArray([
                'properties' => [
                    'name' => ['type' => 'string'],
                    'note' => ['type' => 'string'],
                    'list' => ['type' => 'array', 'x-mapping' => 'item', 'items' => ['type' => 'string']],
                ],
            ]),
        )
        ->sourceSettings([TextInput::make('alias')->dehydrateStateUsing(fn (string $state) => strtoupper($state))])
        ->staticForms(fn (Schema $property) => $property->singleType() === 'string'
            ? TextInput::make('value')->default('fallback')->required()->maxLength(10)
            : null);
    $schema = FilamentSchema::make(SchemaHost::make())->statePath('data')->components([$field]);
    $schema->fill([
        'mapping' => [
            ['kind' => 'static', 'target' => 'note', 'static' => 'saved', 'options' => []],
            ['kind' => 'source', 'source' => 'first', 'target' => 'name', 'options' => ['alias' => 'first']],
            ['kind' => 'source', 'source' => 'second', 'target' => null, 'options' => ['alias' => 'second']],
        ],
    ]);

    expect($field->getStaticFormSchema('name')->getStatePath())->toBe('data.mapping.3');
    $rows = $field->getRawState();
    $rows[1]['target'] = 'list';
    $rows[1]['position'] = 5;
    $rows[2]['target'] = 'list';
    $rows[2]['position'] = 4;
    $field->rawState($rows);

    expect($schema->getState()['mapping'])->toBe([
        ['kind' => 'static', 'target' => 'note', 'static' => 'saved', 'options' => []],
        ['kind' => 'source', 'source' => 'second', 'target' => 'list', 'options' => ['alias' => 'SECOND']],
        ['kind' => 'source', 'source' => 'first', 'target' => 'list', 'options' => ['alias' => 'FIRST']],
    ]);

    $rows[3]['target'] = 'name';
    $field->rawState($rows);
    $field->getStaticFormSchema('name')->getComponent('value')->rawState('edited');
    expect($field->getStaticFormSchema('name')->getStatePath())->toBe('data.mapping.3');
    $submitted = $schema->getState()['mapping'];
    expect(array_column($submitted, 'target'))->toBe(['note', 'name', 'list', 'list']);
    expect($submitted[1]['static'])->toBe('edited');

    $field->getStaticFormSchema('name')->getComponent('value')->rawState('a value exceeding ten characters');
    expect($schema->getState(...))->toThrow(ValidationException::class);
});

it('omits static rows when their value field is not dehydrated while preserving explicit null values', function () {
    $field = FieldMapping::make('mapping')
        ->jsonSchema(target: Schema::fromArray([
            'properties' => [
                'optional' => ['type' => ['string', 'null']],
                'nullable' => ['type' => ['string', 'null']],
                'zero' => ['type' => 'integer'],
                'disabled' => ['type' => 'boolean'],
            ],
        ]))
        ->staticForms(fn (string $target) => $target === 'disabled'
            ? Toggle::make('value')
            : TextInput::make('value')->dehydrated(fn (mixed $state) => $target !== 'optional' || $state !== null));
    $schema = FilamentSchema::make(SchemaHost::make())->statePath('data')->components([$field]);
    $schema->fill(['mapping' => MappingState::mapping([
        'optional' => MappingState::value(null),
        'nullable' => MappingState::value(null),
        'zero' => MappingState::value(0),
        'disabled' => MappingState::value(false),
    ])]);

    expect($schema->getState()['mapping'])
        ->toBe(MappingState::mapping([
            'nullable' => MappingState::value(null),
            'zero' => MappingState::value(0),
            'disabled' => MappingState::value(false),
        ]));
});

it('validates client ordering and static draft identifiers before submission', function (
    array $metadata,
    string $message,
) {
    $schema = static_mapping_schema(['name.part' => MappingState::value('valid')]);
    $field = $schema->getComponent('mapping');
    expect($schema->getState()['mapping'][0]['static'])->toBe('VALID');
    $rows = $field->getRawState();
    $index = $field->getStaticRowIndex('name.part');
    $rows[$index] = [...$rows[$index], ...$metadata];
    $field->rawState($rows);

    try {
        $schema->getState();
        test()->fail('Invalid client metadata was accepted.');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toBe(['data.mapping' => [$message]]);
    }
})->with([
    'negative position' => [['position' => -1], 'The rows.0.position field must be at least 0.'],
    'nonnumeric position' => [['position' => 'first'], 'The rows.0.position field must be an integer.'],
    'unknown static draft' => [
        ['target' => null, 'draftTarget' => 'unknown'],
        'The selected rows.0.draftTarget is invalid.',
    ],
]);

it('replaces an existing static value even when its destination has no static editor', function () {
    $field = FieldMapping::make('mapping')->jsonSchema(
        source: Schema::fromArray(['properties' => ['name' => ['type' => 'string']]]),
        target: Schema::fromArray(['properties' => ['name' => ['type' => 'string']]]),
    );
    $schema = FilamentSchema::make(SchemaHost::make())->statePath('data')->components([$field]);
    $schema->fill([
        'mapping' => [
            ['kind' => 'static', 'target' => 'name', 'static' => 'saved', 'options' => []],
        ],
    ]);
    $rows = $field->getRawState();
    $rows[0]['target'] = null;
    $rows[0]['draftTarget'] = 'name';
    $rows[1]['target'] = 'name';
    $field->rawState($rows);

    expect($schema->getState()['mapping'])->toBe([
        ['kind' => 'source', 'source' => 'name', 'target' => 'name', 'options' => []],
    ]);
});

it('renders a malformed user row list so native validation can reject it', function () {
    Livewire::test(\PHPinnacle\Eidos\Tests\Fixtures\FieldMappingForm::class)
        ->set('data.mapping', ['unknown' => 'first'])
        ->call('submit')
        ->assertHasErrors(['data.mapping' => 'list']);
});

it('partially updates every changed presentation and fully renders structural schema changes', function (bool $dynamicTarget) {
    $page = Livewire::test(\PHPinnacle\Eidos\Tests\Fixtures\FieldMappingForm::class, [
        'dynamicTarget' => $dynamicTarget,
    ]);
    $keys = $page->instance()->form->getComponent('mapping')->getSourceStateKeys();
    $page->set('data.mapping.0.options.type', 'boolean');
    expect($page->effects)->not->toHaveKey('html');
    if ($dynamicTarget) {
        expect($page->effects['partials'])->toHaveKey('schema-component::form.mapping');
    } else {
        expect($page->effects['partials'])->not->toHaveKey('schema-component::form.mapping');
        foreach ($keys as $key) {
            expect($page->effects['partials']['schema-component::form.mapping.heading_' . $key])
                ->toContain('>boolean</span>');
        }
    }

    expect($page->instance()->form->getComponent('mapping')->getConnectionModes())->toBe([
        'first' => ['destination' => MappingMode::Whole],
        'second' => ['destination' => MappingMode::Whole],
    ]);
})->with([false, true]);

it('refreshes source cards when a reactive schema changes its types and annotations', function (string $component) {
    $definition = ['properties' => ['code' => ['title' => 'Code', 'type' => 'string', 'maxLength' => 10]]];
    $source = function () use (&$definition) {
        return Schema::fromArray($definition);
    };
    $field = $component === FieldBinding::class
        ? FieldBinding::make('mapping')->jsonSchema($source)->simple(TextInput::make('target'))
        : FieldMapping::make('mapping')->jsonSchema(
            source: $source,
            target: Schema::fromArray([]),
        );
    FilamentSchema::make(SchemaHost::make())->statePath('data')->components([$field])->fill(['mapping' => []]);
    expect($field->getSources()[0]['presentation']['type'])->toBe('string');
    $definition = [
        'properties' => ['code' => ['title' => 'Quantity', 'type' => 'integer', 'minimum' => 0]],
        'required' => ['code'],
    ];
    $card = $field->getSources()[0];
    expect($card['label'])
        ->toBe('Quantity')
        ->and($card['required'])
        ->toBeTrue()
        ->and($card['presentation']['type'])
        ->toBe('integer')
        ->and($card['presentation']['constraints'])
        ->toBe([['kind' => 'range', 'value' => '≥ 0', 'scope' => []]]);
})->with(['binding' => [FieldBinding::class], 'mapping' => [FieldMapping::class]]);

/**
 * @param array<array-key, mixed> $mapping
 * @param list<string> $targets
 */
function static_mapping_schema(array $mapping, array $targets = []): FilamentSchema
{
    $livewire = SchemaHost::make();
    $field = FieldMapping::make('mapping')
        ->jsonSchema(
            source: Schema::fromArray(['properties' => ['source' => ['type' => 'string']]]),
            target: Schema::fromArray([
                'properties' => array_intersect_key(
                    [
                        'name.part' => ['type' => ['string', 'null']],
                        'enabled' => ['type' => 'boolean'],
                        'count' => ['type' => 'number'],
                        '0' => ['type' => 'string'],
                        'raw' => ['type' => 'integer'],
                        'tags' => ['type' => 'array', 'x-mapping' => 'item', 'items' => ['type' => 'string']],
                    ],
                    array_fill_keys([...array_keys($mapping), ...$targets], true),
                ),
            ]),
        )
        ->staticForms(fn (string $target, Schema $property) => match ($property->singleType()) {
            'string' => TextInput::make('value')
                ->label($target)
                ->required()
                ->maxLength(5)
                ->dehydrateStateUsing(fn (string $state) => strtoupper($state)),
            'boolean' => Toggle::make('value')->default(false),
            'number' => TextInput::make('value')->numeric()->required()->minValue(0),
            'array' => TagsInput::make('value')->required()->nestedRecursiveRules(['string']),
            default => null,
        })
        ->dehydrateStateUsing(fn (array $state) => $state);

    $schema = FilamentSchema::make($livewire)->statePath('data')->components([$field]);
    $schema->fill(['mapping' => MappingState::mapping($mapping)]);

    return $schema;
}

it('selects static editors by target without prohibiting submitted scalar values', function () {
    $field = FieldMapping::make('mapping')
        ->jsonSchema(target: Schema::fromArray([
            'properties' => [
                'id' => ['type' => 'string'],
                'name' => ['type' => 'string', 'maxLength' => 5],
            ],
        ]))
        ->staticForms(fn (string $target, Schema $property) => $target === 'id'
            ? null
            : TextInput::make('value')->maxLength($property->annotation('maxLength')));
    $schema = FilamentSchema::make(SchemaHost::make())->statePath('data')->components([$field]);
    $mapping = MappingState::mapping([
        'id' => MappingState::value('record-1'),
        'name' => MappingState::value('Alice'),
    ]);
    $schema->fill(['mapping' => $mapping]);
    view()->share('errors', new ViewErrorBag);

    expect($field->getStaticFormSchema('id'))
        ->toBeNull()
        ->and($field->getStaticDefaults())
        ->not->toHaveKey('id')->and($schema->toHtml())->toContain('data-mapping-static-form="name"')
        ->not->toContain('data-mapping-static-form="id"')->and($schema->getState()['mapping'])->toBe($mapping);

    $field->getStaticFormSchema('name')->getComponent('value')->state('Too long');
    expect($schema->getState(...))->toThrow(ValidationException::class);
});

it('offers registered static editors for every destination and validates their defaults', function () {
    $schema = static_mapping_schema([], ['name.part', 'enabled', 'raw']);
    view()->share('errors', new ViewErrorBag);

    expect($schema->toHtml())
        ->toContain('data-mapping-static-form="name.part"')
        ->toContain('data-mapping-static-form="enabled"')
        ->not->toContain('data-mapping-static-form="raw"');
    expect($schema->getState(...))->toThrow(ValidationException::class);

    $schema
        ->getComponent('mapping')
        ->staticForms(fn (string $target, Schema $property) => match ($property->singleType()) {
            'string' => TextInput::make('value')->nullable(),
            'boolean' => Toggle::make('value')->default(false),
            default => null,
        });
    $schema->fill(['mapping' => MappingState::mapping([])]);
    expect($schema->getState()['mapping'])
        ->toEqualCanonicalizing(MappingState::mapping(
            [
                'name.part' => MappingState::value(null),
                'enabled' => MappingState::value(false),
            ],
            [],
            array_column($schema->getComponent('mapping')->getSources(), 'id'),
        ));

    $schema->fill(['mapping' => MappingState::mapping(['name.part' => MappingState::source('source')])]);
    expect($schema->getState()['mapping'])
        ->toEqualCanonicalizing(MappingState::mapping(
            [
                'name.part' => MappingState::source('source'),
                'enabled' => MappingState::value(false),
            ],
            [],
            array_column($schema->getComponent('mapping')->getSources(), 'id'),
        ));
});

it('renders inline static forms and round trips typed values and literal identifiers', function () {
    $schema = static_mapping_schema([
        'name.part' => MappingState::value('hello'),
        'enabled' => MappingState::value(false),
        'count' => MappingState::value(0),
        '0' => MappingState::value('007'),
        'raw' => MappingState::value('009'),
    ]);
    view()->share('errors', new ViewErrorBag);

    expect($schema->toHtml())->toContain('role="switch"');
    expect($schema->getState()['mapping'])
        ->toBe(MappingState::mapping(
            [
                'name.part' => MappingState::value('HELLO'),
                'enabled' => MappingState::value(false),
                'count' => MappingState::value(0.0),
                '0' => MappingState::value('007'),
                'raw' => MappingState::value('009'),
            ],
            [],
            array_column($schema->getComponent('mapping')->getSources(), 'id'),
        ));
});

it('supports array static editors alongside collection connections', function (array $mapping) {
    $schema = static_mapping_schema($mapping);
    expect($schema->getState()['mapping'])->toBe(MappingState::mapping($mapping, [], ['source']));
})->with([
    'static array' => [['tags' => MappingState::value(['one', 'two'])]],
    'collected source' => [['tags' => [MappingState::source('source')]]],
]);

it('validates static values through their registered Filament fields', function (array $mapping) {
    $schema = static_mapping_schema($mapping);

    expect($schema->getState(...))->toThrow(ValidationException::class);
})->with([
    'required text' => [['name.part' => MappingState::value('')]],
    'long text' => [['name.part' => MappingState::value('longer than five')]],
    'negative number' => [['count' => MappingState::value(-1)]],
    'empty static value without an editor' => [['raw' => MappingState::value('')]],
]);

it('only renders one mapping search input when enabled, including closure configuration', function () {
    $livewire = SchemaHost::make();

    $field = FieldMapping::make('mapping')->jsonSchema(
        source: Schema::fromArray(['properties' => ['title' => []]]),
        target: Schema::fromArray([
            'properties' => ['name' => []],
        ]),
    );
    $schema = FilamentSchema::make($livewire)->statePath('data')->components([$field]);
    $schema->fill(['mapping' => MappingState::mapping(['name' => MappingState::source('title')])]);
    view()->share('errors', new ViewErrorBag);

    expect($schema->toHtml())->not->toContain('type="search"');
    $field->searchable();
    expect(substr_count($schema->toHtml(), 'type="search"'))->toBe(1);
    $field->searchable(false);
    expect($schema->toHtml())->not->toContain('type="search"');

    $enabled = true;
    $field->searchable(function () use (&$enabled) {
        return $enabled;
    });
    expect(substr_count($schema->toHtml(), 'type="search"'))->toBe(1);
    $enabled = false;
    expect($schema->toHtml())->not->toContain('type="search"');
    expect($schema->getState()['mapping'])
        ->toBe(MappingState::mapping(
            ['name' => MappingState::source('title')],
            [],
            array_column($schema->getComponent('mapping')->getSources(), 'id'),
        ));
});

it('places one shared search before the toolbar filter for mapping and binding', function (
    bool $binding,
    bool $contained,
) {
    $source = ['properties' => ['name' => ['type' => 'string']]];
    $field = $binding
        ? FieldBinding::make('mapping')
            ->jsonSchema(Schema::fromArray($source))
            ->simple(TextInput::make('destination'))
        : FieldMapping::make('mapping')->jsonSchema(
            source: Schema::fromArray($source),
            target: Schema::fromArray($source),
        );
    $field->searchable()->contained($contained);
    $schema = FilamentSchema::make(SchemaHost::make())->statePath('data')->components([$field]);
    $schema->fill();
    $state = $schema->getState();
    view()->share('errors', new ViewErrorBag);
    $document = new DOMDocument;
    $document->loadHTML($schema->toHtml(), LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($document);
    $searches = $xpath->query('//input[@type="search"]');

    expect($searches)->toHaveCount($contained ? 1 : 0);
    if (!$contained) {
        expect($xpath->query('//div[contains(@class, "eidos-mapping-toolbar")]'))->toHaveCount(0);
        expect($schema->getState())->toBe($state);

        return;
    }

    $search = $searches->item(0);
    expect($search->getAttribute('x-model'))
        ->toBe('search')
        ->and($search->getAttribute('aria-label'))
        ->toBe('Search fields');
    expect($xpath->query('ancestor::div[contains(@class, "eidos-mapping-toolbar")]', $search))->toHaveCount(1);
    expect($xpath->query('following::fieldset[contains(@class, "eidos-mapping-filter")]', $search))
        ->toHaveCount($contained ? 1 : 0);
    expect($schema->getState())->toBe($state);
})->with([false, true])->with([false, true]);

it('keeps static values in destination editors without creating source cards', function (bool $inverse) {
    $sourceSchema = ['properties' => ['name' => ['type' => 'string']]];
    $field = FieldMapping::make('mapping')
        ->jsonSchema(
            source: Schema::fromArray($sourceSchema),
            target: Schema::fromArray(['properties' => ['label' => ['type' => 'string']]]),
        )
        ->withBuiltinStaticForms()
        ->inverse($inverse);
    $schema = FilamentSchema::make(SchemaHost::make())->statePath('data')->components([$field]);
    $schema->fill([
        'mapping' => [
            ['kind' => 'static', 'target' => 'label', 'static' => 'Customer', 'options' => []],
        ],
    ]);
    view()->share('errors', new ViewErrorBag);

    expect($field->getSourceSchema()->toArray())
        ->toBe($sourceSchema)
        ->and(array_column($field->getSources(), 'id'))
        ->toBe(['name'])
        ->and($field->getSourceRowIndexes())
        ->toBe(['name' => 1])
        ->and($schema->toHtml())
        ->toContain('data-mapping-static-form="label"')
        ->not->toContain('data-mapping-static-source', 'Add static value', 'removeConstant');
    expect($schema->getState()['mapping'])->toBe([
        ['kind' => 'static', 'target' => 'label', 'static' => 'Customer', 'options' => []],
        ['kind' => 'source', 'source' => 'name', 'target' => null, 'options' => []],
    ]);
})->with([false, true]);

it('filters complete source card containers', function (bool $inverse) {
    $field = FieldMapping::make('mapping')
        ->jsonSchema(
            source: Schema::fromArray(['properties' => ['title' => ['type' => 'string']]]),
            target: Schema::fromArray([
                'properties' => ['name' => ['type' => 'string']],
            ]),
        )
        ->inverse($inverse);
    $schema = FilamentSchema::make(SchemaHost::make())->statePath('data')->components([$field]);
    $schema->fill([
        'mapping' => [
            ['kind' => 'source', 'source' => 'title', 'target' => null, 'options' => []],
        ],
    ]);
    view()->share('errors', new ViewErrorBag);

    $document = new DOMDocument;
    $document->loadHTML($schema->toHtml(), LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($document);
    $sourceCards = $xpath->query('//fieldset[div/div[@data-mapping-row]]');

    expect($sourceCards)->toHaveCount(1);
    foreach ($sourceCards as $card) {
        expect($card->getAttribute('x-bind:class'))->toContain('fi-hidden', "isVisible('source',");
        expect($xpath->query('./div/div[@data-mapping-row and @x-show]', $card))->toHaveCount(0);
    }

    expect($schema->getState()['mapping'])->toBe([
        ['kind' => 'source', 'source' => 'title', 'target' => null, 'options' => []],
    ]);
})->with([false, true]);

it('applies column spans to their source and target sides without changing mappings', function (bool $inverse) {
    $field = FieldMapping::make('mapping')
        ->jsonSchema(
            source: Schema::fromArray(['properties' => ['title' => ['type' => 'string']]]),
            target: Schema::fromArray([
                'properties' => ['name' => ['type' => 'string']],
            ]),
        )
        ->inverse($inverse);
    $schema = FilamentSchema::make(SchemaHost::make())->statePath('data')->components([$field]);
    $schema->fill(['mapping' => MappingState::mapping(['name' => MappingState::source('title')])]);
    view()->share('errors', new ViewErrorBag);
    $state = $schema->getState();

    expect($field->getColumnSpans())->toBe(['source' => 5, 'target' => 5, 'gap' => 2]);
    expect($schema->toHtml())
        ->toContain('--eidos-left-column: 5fr', '--eidos-gap-column: 2fr', '--eidos-right-column: 5fr');
    $field->columnSpans(source: 4, target: 7, gap: 1);
    expect($schema->toHtml())
        ->toContain(
            '--eidos-left-column: ' . ($inverse ? 7 : 4) . 'fr',
            '--eidos-gap-column: 1fr',
            '--eidos-right-column: ' . ($inverse ? 4 : 7) . 'fr',
        );
    expect($schema->getState())->toBe($state);
})->with([false, true]);

it('preserves standalone mapping content and values across presentation options', function (
    bool $contained,
    bool $dense,
    bool $compact,
) {
    $livewire = SchemaHost::make();

    $field = FieldMapping::make('mapping')
        ->jsonSchema(
            source: Schema::fromArray([
                'properties' => [
                    'title' => [
                        'type' => 'string',
                        'description' => 'The incoming display name.',
                        'x-badges' => [['label' => 'Imported', 'color' => 'gray']],
                    ],
                ],
            ]),
            target: Schema::fromArray([
                'required' => ['name'],
                'properties' => [
                    'name' => [
                        'type' => 'string',
                        'description' => 'The destination display name.',
                        'x-icon' => 'heroicon-o-user',
                        'x-badges' => [['label' => 'Unique', 'color' => 'gray']],
                    ],
                ],
            ]),
        )
        ->dense(fn () => $dense)
        ->compact(fn () => $compact)
        ->columnSpanFull();
    if (!$contained) {
        $field->contained(false);
    }

    $schema = FilamentSchema::make($livewire)
        ->statePath('data')
        ->components([$field]);
    $schema->fill(['mapping' => MappingState::mapping(['name' => MappingState::source('title')])]);

    view()->share('errors', new ViewErrorBag);

    $html = $schema->toHtml();

    $text = preg_replace('/\s+/', ' ', strip_tags($html));
    expect($text)->toContain('Required', 'Unique string', 'Imported string');
    expect(str_contains($text, 'The incoming display name.'))->toBe(!$compact);
    expect(str_contains($text, 'The destination display name.'))->toBe(!$compact);
    expect($html)->toContain('The incoming display name.', 'The destination display name.');
    expect(str_contains($html, 'data-mapping-description'))->toBe(!$compact);
    expect(str_contains($html, 'x-tooltip="{ content:'))->toBe($compact);
    expect(str_contains($html, 'fi-dense'))->toBe($dense);
    expect($html)->toContain('eidos-mapping-card field-mapping-row');
    expect(str_contains($text, 'Field visibility'))->toBe($contained);
    expect(substr_count($html, 'type="radio"'))->toBe($contained ? 3 : 0);
    if ($contained) {
        expect($text)->toContain('All', 'Mapped', 'Unmapped');
    }

    expect($html)->toContain('aria-live="polite"');
    expect($html)->toContain(FilamentAsset::getAlpineComponentSrc('field-mapping', 'phpinnacle/eidos'));
    expect($schema->getState()['mapping'])
        ->toBe(MappingState::mapping(
            ['name' => MappingState::source('title')],
            [],
            array_column($schema->getComponent('mapping')->getSources(), 'id'),
        ));
})->with([
    'baseline' => [true, false, false],
    'without container' => [false, false, false],
    'dense' => [true, true, false],
    'compact' => [true, false, true],
    'combined' => [false, true, true],
]);

it('keeps the mapping toolbar when either side has no fields', function (
    array $sources,
    array $targets,
) {
    $livewire = SchemaHost::make();

    $schema = FilamentSchema::make($livewire)
        ->statePath('data')
        ->components([
            FieldMapping::make('mapping')
                ->jsonSchema(
                    source: Schema::fromArray(['properties' => array_fill_keys($sources, [])]),
                    target: Schema::fromArray(['properties' => array_fill_keys($targets, [])]),
                )
                ->searchable()
                ->warnings(dest: ['name' => 'Review the destination.'])
                ->labels(source: 'Incoming fields', dest: 'Output fields'),
        ]);
    $schema->fill(['mapping' => MappingState::mapping([])]);
    view()->share('errors', new ViewErrorBag);

    expect($schema->toHtml())
        ->toContain('No fields available.')
        ->not->toContain('Incoming fields')
        ->not->toContain('Output fields')
        ->not->toContain('type="search"')->toContain('Field visibility')->toContain('Review the destination.');
})->with([
    'no source fields' => [[], ['name']],
    'no target fields' => [['title'], []],
    'no fields' => [[], []],
]);

it('highlights warnings on either side and preserves missing identifiers in both orientations', function (bool $inverse) {
    $livewire = SchemaHost::make();

    $schema = FilamentSchema::make($livewire)
        ->statePath('data')
        ->components([
            FieldMapping::make('mapping')
                ->jsonSchema(
                    source: Schema::fromArray(['properties' => ['title' => [], 'email' => []]]),
                    target: Schema::fromArray([
                        'properties' => ['name' => [], 'contact' => []],
                    ]),
                )
                ->warnings(
                    source: fn () => ['title' => 'Source <type> changed.', '42' => 'Source disappeared.'],
                    dest: ['name' => 'Destination changed.', '42' => 'Destination disappeared.'],
                )
                ->inverse($inverse),
        ]);
    $schema->fill(['mapping' => MappingState::mapping(['name' => MappingState::source('title')])]);
    view()->share('errors', new ViewErrorBag);

    $html = $schema->toHtml();

    expect($html)
        ->toContain('Source &lt;type&gt; changed.')
        ->toContain('Destination changed.')
        ->toContain('42: Source disappeared.')
        ->toContain('42: Destination disappeared.');
})->with([false, true]);

it('renders native actions on every source before mapping in both orientations', function (bool $inverse) {
    $livewire = SchemaHost::make();

    $schema = FilamentSchema::make($livewire)
        ->statePath('data')
        ->components([
            FieldMapping::make('mapping')
                ->jsonSchema(
                    source: Schema::fromArray(['properties' => ['title' => [], 'email' => []]]),
                    target: Schema::fromArray([
                        'properties' => ['name' => [], 'contact' => []],
                    ]),
                )
                ->sourceSettings(fn (string $source) => [
                    Actions::make([
                        Action::make('inspect')->label('Inspect ' . $source),
                        Action::make('hidden')
                            ->hidden()
                            ->label(
                                fn () => throw new LogicException('Hidden actions must not evaluate presentation.'),
                            ),
                    ]),
                ])
                ->inverse($inverse),
        ]);
    $schema->fill(['mapping' => MappingState::mapping([])]);
    view()->share('errors', new ViewErrorBag);

    $html = $schema->toHtml();

    expect($html)
        ->toContain('Inspect title')
        ->toContain('Inspect email');
})->with([false, true]);

it('does not render static editors without a registered type factory', function (bool $inverse) {
    $livewire = SchemaHost::make();

    $schema = FilamentSchema::make($livewire)
        ->statePath('data')
        ->components([
            FieldMapping::make('mapping')
                ->jsonSchema(
                    source: Schema::fromArray(['properties' => ['title' => []]]),
                    target: Schema::fromArray([
                        'properties' => ['name' => [], 'contact' => []],
                    ]),
                )
                ->inverse($inverse),
        ]);
    $schema->fill(['mapping' => MappingState::mapping([])]);
    view()->share('errors', new ViewErrorBag);

    $html = $schema->toHtml();

    expect($html)
        ->not->toContain('data-mapping-static-form');
})->with([false, true]);

it('renders schema names, formats and separate disclosure controls for scoped details', function () {
    $field = FieldMapping::make('mapping')->jsonSchema(
        source: Schema::fromArray([
            'properties' => [
                'tag_codes' => [
                    'title' => 'Tags',
                    'type' => 'array',
                    'maxItems' => 10,
                    'uniqueItems' => true,
                    'items' => ['type' => 'string', 'maxLength' => 16],
                    'x-icon' => 'heroicon-o-tag',
                    'x-color' => 'violet',
                ],
            ],
        ]),
        target: Schema::fromArray([
            'properties' => [
                'id' => ['title' => 'Identifier', 'type' => 'string', 'format' => 'uuid'],
                'status' => ['type' => 'string', 'enum' => ['active', '<draft>']],
            ],
        ]),
    );
    $schema = FilamentSchema::make(SchemaHost::make())->statePath('data')->components([$field]);
    $schema->fill(['mapping' => MappingState::mapping([])]);
    view()->share('errors', new ViewErrorBag);

    $html = $schema->toHtml();
    expect($html)
        ->toContain(
            'aria-label="Items: ≤ 10"',
            'aria-label="Items: Length: ≤ 16"',
            'data-mapping-constraint="unique"',
            '&lt;draft&gt;',
            'var(--violet-600)',
            'Show item types for Tags',
            'data-mapping-details',
            ':aria-controls=',
        )
        ->and(strip_tags($html))
        ->toContain('Tags', 'tag_codes', 'string[]', 'uuid', 'enum')
        ->and($html)
        ->not->toContain('Show item types for status');
});

it('shows output metadata without retaining the input format or limits', function () {
    $field = FieldMapping::make('mapping')
        ->jsonSchema(
            source: Schema::fromArray([
                'properties' => ['id' => [
                    'title' => 'Identifier',
                    'type' => 'string',
                    'format' => 'uuid',
                    'maxLength' => 36,
                ]],
            ]),
            target: Schema::fromArray(['properties' => ['enabled' => ['type' => 'boolean']]]),
        )
        ->outputSchema(fn () => Schema::fromArray(['properties' => ['id' => ['type' => 'boolean']]]));
    $schema = FilamentSchema::make(SchemaHost::make())->statePath('data')->components([$field]);
    $schema->fill(['mapping' => MappingState::mapping([])]);
    view()->share('errors', new ViewErrorBag);

    $html = $schema->toHtml();
    expect(strip_tags($html))
        ->toContain('boolean')
        ->not->toContain('uuid')->and($html)
        ->not->toContain('data-mapping-constraint="length"', 'data-mapping-details-toggle');
});

it('shows only the final type and uses it for connection checks', function () {
    $livewire = SchemaHost::make();

    $field = FieldMapping::make('mapping')
        ->jsonSchema(
            source: Schema::fromArray(['properties' => ['active' => ['type' => 'boolean']]]),
            target: Schema::fromArray([
                'properties' => ['name' => ['type' => 'string']],
            ]),
        )
        ->outputSchema(fn () => Schema::fromArray(['properties' => ['active' => ['type' => 'string']]]));
    $schema = FilamentSchema::make($livewire)->statePath('data')->components([$field]);
    $schema->fill(['mapping' => MappingState::mapping(['name' => MappingState::source('active')])]);
    view()->share('errors', new ViewErrorBag);

    $html = $schema->toHtml();

    expect(strip_tags($html))->toContain('string')->not->toContain('boolean', '→');
    expect($field->getSources()[0]['type'])->toBe('boolean');
    expect($schema->getState()['mapping'])
        ->toBe(MappingState::mapping(
            ['name' => MappingState::source('active')],
            [],
            array_column($schema->getComponent('mapping')->getSources(), 'id'),
        ));
});

it('persists and validates source settings independently of connections', function (bool $inverse) {
    $field = FieldMapping::make('mapping')
        ->jsonSchema(
            source: Schema::fromArray([
                'properties' => ['first.part' => ['type' => 'string'], '0' => ['type' => 'string']],
            ]),
            target: Schema::fromArray(['properties' => ['name' => ['type' => 'string']]]),
        )
        ->sourceSettings(fn (string $source) => [
            TextInput::make('alias')->default($source)->required(),
            Toggle::make('enabled')->default(true),
        ])
        ->inverse($inverse);
    $schema = FilamentSchema::make(SchemaHost::make())->statePath('data')->components([$field]);
    $settings = [
        'first.part' => ['alias' => 'First', 'enabled' => false],
        '0' => ['alias' => 'Zero', 'enabled' => true],
    ];
    foreach ([
        ['name' => MappingState::source('first.part')],
        ['name' => MappingState::value('static')],
        [],
    ] as $targets) {
        $schema->fill(['mapping' => MappingState::mapping($targets, $settings)]);
        if (array_key_exists('name', $targets) && $targets['name']['kind'] === 'source') {
            expect($field->getSourceSettings()['first.part'])->toBe($settings['first.part']);
        }

        expect($schema->getState()['mapping'])->toBe(MappingState::mapping($targets, $settings));
    }

    $schema->fill(['mapping' => MappingState::mapping([])]);
    expect($field->getSourceSettings())->toBe([
        'first.part' => ['alias' => 'first.part', 'enabled' => true],
        '0' => ['alias' => '0', 'enabled' => true],
    ]);
    $schema->getComponentByStatePath('mapping.0.options.alias')->state('');
    expect($schema->getState(...))->toThrow(ValidationException::class);
    $schema->fill(['mapping' => MappingState::mapping(['name' => MappingState::source('first.part')], ['first.part' => [
        'alias' => '',
        'enabled' => false,
    ]])]);
    expect($schema->getState(...))->toThrow(ValidationException::class);
})->with([false, true]);

it('reads and submits canonical nested source settings without changing editor state', function (bool $binding) {
    $sourceSchema = ['properties' => ['first.part' => ['type' => 'string'], '0' => ['type' => 'string']]];
    $field = $binding
        ? FieldBinding::make('widget')
            ->jsonSchema(Schema::fromArray($sourceSchema))
            ->simple(TextInput::make('column'))
        : FieldMapping::make('widget')->jsonSchema(
            source: Schema::fromArray($sourceSchema),
            target: Schema::fromArray([
                'properties' => ['first' => [], 'second' => []],
            ]),
        );
    $field->sourceSettings(fn (string $source) => [
        Repeater::make('rules')->schema([TextInput::make('value')->dehydrateStateUsing(
            fn (string $state) => strtoupper($state),
        )]),
        Builder::make('blocks')->blocks([Block::make('replace')->schema([TextInput::make('value')])]),
        KeyValue::make('mapping')->label('Mapping for ' . $source),
    ]);
    $schema = FilamentSchema::make(SchemaHost::make())->statePath('data')->components([$field]);
    $settings = [
        'first.part' => [
            'rules' => [['value' => 'first']],
            'blocks' => [['type' => 'replace', 'data' => ['value' => 'One']]],
            'mapping' => [' padded ' => '', '0' => '  '],
            'custom' => ['keep' => 'as provided'],
        ],
        '0' => [
            'rules' => [['value' => 'second']],
            'blocks' => [['type' => 'replace', 'data' => ['value' => 'Two']]],
            'mapping' => ['second' => 'value'],
        ],
    ];
    $entries = $binding
        ? MappingState::binding(['first.part' => 'first', '0' => 'second'], $settings)
        : MappingState::mapping([
            'first' => MappingState::source('first.part'),
            'second' => MappingState::source('0'),
        ], $settings);
    $schema->fill(['widget' => $entries]);
    $editorState = $field->getRawState();
    $settings['first.part']['rules'][0]['value'] = 'FIRST';
    $settings['0']['rules'][0]['value'] = 'SECOND';
    expect($field->getSourceSettings())->toBe($settings);
    expect($field->getRawState())->toBe($editorState);
    $expected = $binding
        ? MappingState::binding(['first.part' => 'first', '0' => 'second'], $settings)
        : MappingState::mapping([
            'first' => MappingState::source('first.part'),
            'second' => MappingState::source('0'),
        ], $settings);
    expect($schema->getState()['widget'])->toBe($expected);
    $field->rawState(array_reverse($editorState));
    expect($field->getSourceSettings())->toBe($settings);
    expect($schema->getComponentByStatePath('widget.0.options.mapping')->getLabel())->toBe('Mapping for 0');
})->with([false, true]);

it('shows settings for newly available sources without requiring a connection or binding', function (bool $binding) {
    $sources = ['name'];
    $sourceSchema = function () use (&$sources) {
        return Schema::fromArray(['properties' => array_fill_keys($sources, ['type' => 'string'])]);
    };
    $field = $binding
        ? FieldBinding::make('widget')->jsonSchema($sourceSchema)->simple(TextInput::make('column'))
        : FieldMapping::make('widget')->jsonSchema(source: $sourceSchema, target: Schema::fromArray([
            'properties' => ['output' => ['type' => 'string']],
        ]));
    $field->sourceSettings(fn (string $source) => [TextInput::make('alias')->label('Alias for ' . $source)]);
    $schema = FilamentSchema::make(SchemaHost::make())->statePath('data')->components([$field]);
    $schema->fill(['widget' => []]);
    view()->share('errors', new ViewErrorBag);
    expect($schema->toHtml())->toContain('Alias for name')->not->toContain('Alias for email');
    $sources[] = 'email';
    expect($schema->toHtml())->toContain('Alias for name', 'Alias for email');
})->with([false, true]);

it('shows the property key separately from its title and preserves mapping state', function (bool $binding) {
    $sourceId = '63e1c289-8a21-4ac1-bfb0-353d38184bfd';
    $sourceSchema = ['properties' => [$sourceId => ['type' => 'string', 'title' => 'Email']]];
    $field = $binding
        ? FieldBinding::make('widget')
            ->jsonSchema(Schema::fromArray($sourceSchema))
            ->simple(TextInput::make('destination')->default('contact.email'))
        : FieldMapping::make('widget')->jsonSchema(
            source: Schema::fromArray($sourceSchema),
            target: Schema::fromArray([
                'properties' => ['email' => ['type' => 'string']],
            ]),
        );
    $field->searchable();
    $schema = FilamentSchema::make(SchemaHost::make())->statePath('data')->components([$field]);
    $state = $binding
        ? MappingState::binding([$sourceId => 'contact.email'])
        : MappingState::mapping(['email' => MappingState::source($sourceId)]);
    $schema->fill(['widget' => $state]);
    view()->share('errors', new ViewErrorBag);

    $html = $schema->toHtml();
    preg_match('/<span class="eidos-mapping-property">([^<]+)<\/span>/', $html, $technicalName);
    expect($technicalName[1])->toBe($sourceId);
    expect($field->getSources()[0]['id'])->toBe($sourceId);
    expect($field->getSources()[0]['label'])->toBe('Email');
    expect($schema->getState()['widget'])->toBe($state);
})->with([false, true]);

it('keeps header actions and the trailing filter in one toolbar with density defaults', function (
    bool $binding,
    bool $contained,
    bool $dense,
) {
    require_once __DIR__ . '/../Fixtures/MappingHeaderActionsForm.php';
    $page = Livewire::test(\PHPinnacle\Eidos\Tests\Fixtures\MappingHeaderActionsForm::class, [
        'binding' => $binding,
        'contained' => $contained,
    ]);
    $field = $page->instance()->form->getComponent('mapping')->dense($dense);
    view()->share('errors', new ViewErrorBag);
    $document = new DOMDocument;
    $document->loadHTML($page->instance()->form->toHtml(), LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($document);
    $toolbars = $xpath->query('//div[contains(@class, "eidos-mapping-toolbar")]');
    expect($toolbars)->toHaveCount(1);
    $toolbar = $toolbars->item(0);
    expect(str_contains($toolbar->parentNode->getAttribute('class'), 'fi-dense'))->toBe($dense);
    expect($xpath->query('./div[1]//button', $toolbar))->toHaveCount(1);
    expect($xpath->query('./div[2]/fieldset[last()]', $toolbar))->toHaveCount($contained ? 1 : 0);
    expect($field->getAction('addSource')->getSize())
        ->toBe($dense ? \Filament\Support\Enums\Size::Small : \Filament\Support\Enums\Size::Medium);
    $field->getAction('addSource')->size(\Filament\Support\Enums\Size::Large);
    $page->instance()->form->toHtml();
    expect($field->getAction('addSource')->getSize())->toBe(\Filament\Support\Enums\Size::Large);
})->with([
    'mapping baseline' => [false, true, false],
    'binding baseline' => [true, true, false],
    'mapping without container' => [false, false, false],
    'binding without container' => [true, false, false],
    'dense mapping' => [false, true, true],
    'dense binding' => [true, true, true],
]);

it('uses native row state in owning header actions across Livewire requests', function (
    bool $binding,
    bool $contained,
) {
    require_once __DIR__ . '/../Fixtures/MappingHeaderActionsForm.php';
    $page = Livewire::test(\PHPinnacle\Eidos\Tests\Fixtures\MappingHeaderActionsForm::class, [
        'binding' => $binding,
        'contained' => $contained,
    ]);
    $action = \Filament\Actions\Testing\TestAction::make('addSource')->schemaComponent('mapping', 'form');
    $page
        ->assertSee('Add source')
        ->callAction($action, data: ['field' => 'record.id'])
        ->assertHasNoErrors()
        ->assertSet('owner', $binding ? FieldBinding::class : FieldMapping::class)
        ->assertSet('data.mapping.0.source', 'record.id')
        ->assertSet(
            'data.mapping.0.options.transforms',
            fn (array $items) => array_values($items) === [['type' => 'trim']],
        )
        ->set('data.mapping.0.target', 'destination')
        ->callAction($action, data: ['field' => 'second'])
        ->assertHasNoErrors()
        ->assertSet('data.mapping.0.target', 'destination')
        ->assertSet('data.mapping.1.source', 'second')
        ->call('$refresh')
        ->assertSee('record.id')
        ->assertSee('second')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('saved.mapping', [
            [
                'kind' => 'source',
                'source' => 'record.id',
                'target' => 'destination',
                'options' => ['transforms' => [['type' => 'TRIM']]],
            ],
            [
                'kind' => 'source',
                'source' => 'second',
                'target' => null,
                'options' => ['transforms' => [['type' => 'TRIM']]],
            ],
        ]);
    $field = $page->instance()->form->getComponent('mapping');
    expect($field->getState())->toBe($page->get('data.mapping'));
    expect(array_is_list($field->getState()))->toBeTrue();
    if (!$binding) {
        expect($field->getTargetSchema()->toArray())->toBe(['properties' => ['destination' => ['type' => 'string']]]);
    }
})->with([false, true])->with([false, true]);

it('rebinds native settings and static editors to their current row indexes', function () {
    $field = FieldMapping::make('mapping')
        ->jsonSchema(
            source: Schema::fromArray([
                'properties' => ['first' => ['type' => 'string'], 'second' => ['type' => 'string']],
            ]),
            target: Schema::fromArray([
                'properties' => [
                    'items' => ['type' => 'array', 'x-mapping' => 'item', 'items' => ['type' => 'string']],
                    'label' => ['type' => 'string'],
                ],
            ]),
        )
        ->sourceSettings([Repeater::make('rules')
            ->schema([TextInput::make('value')])
            ->defaultItems(0)])
        ->staticForms(fn (string $target) => $target === 'label' ? TextInput::make('value') : null);
    $schema = FilamentSchema::make(SchemaHost::make())->statePath('data')->components([$field]);
    $first = [
        'kind' => 'source',
        'source' => 'first',
        'target' => null,
        'options' => ['rules' => [['value' => 'First']]],
    ];
    $second = [
        'kind' => 'source',
        'source' => 'second',
        'target' => null,
        'options' => ['rules' => [['value' => 'Second']]],
    ];
    $constant = ['kind' => 'static', 'target' => 'label', 'static' => 'Saved label', 'options' => []];
    $schema->fill(['mapping' => [$first, $second, $constant]]);
    $field->getChildSchemas();
    $rows = $field->getState();
    $rows[0]['target'] = 'items';
    $rows[1]['target'] = 'items';
    $field->state([$rows[1], $rows[2], $rows[0]]);
    $children = $field->getChildSchemas();
    $keys = $field->getSourceStateKeys();
    expect($children['settings_' . $keys['first']]->getStatePath())->toBe('data.mapping.2.options');
    expect($children['settings_' . $keys['second']]->getStatePath())->toBe('data.mapping.0.options');
    expect($field->getStaticFormSchema('label')->getComponent('value')->getStatePath())->toBe('data.mapping.1.static');
    $children['settings_' . $keys['first']]->getComponent('rules')->state([['value' => 'Edited first']]);
    $field->getStaticFormSchema('label')->getComponent('value')->state('Edited label');
    $expected = [$second, $constant, $first];
    $expected[0]['target'] = 'items';
    $expected[1]['static'] = 'Edited label';
    $expected[2]['target'] = 'items';
    $expected[2]['options']['rules'] = [['value' => 'Edited first']];
    expect($schema->getState()['mapping'])->toBe($expected);
});

it('round trips collection mappings through independent schema instances', function (string $mode, string $type) {
    $fields = [];
    foreach (['first', 'second'] as $name) {
        $fields[] = FieldMapping::make($name)
            ->jsonSchema(
                source: Schema::fromArray([
                    'properties' => ['_my_field' => ['type' => 'string'], 'other.name' => ['type' => 'string']],
                ]),
                target: Schema::fromArray([
                    'properties' => [
                        'custom' => ['type' => $type, 'x-mapping' => $mode, 'items' => ['type' => 'string']],
                    ],
                ]),
            )
            ->sourceSettings([TextInput::make('alias')]);
    }

    $rows = [
        ['kind' => 'source', 'source' => '_my_field', 'target' => 'custom', 'options' => ['alias' => 'one']],
        ['kind' => 'source', 'source' => 'other.name', 'target' => 'custom', 'options' => ['alias' => 'two']],
    ];
    $schema = FilamentSchema::make(SchemaHost::make())->statePath('data')->components($fields);
    $schema->fill(['first' => $rows, 'second' => $rows]);
    view()->share('errors', new ViewErrorBag);

    expect($schema->getState())->toBe(['first' => $rows, 'second' => $rows]);
    expect($schema->toHtml())
        ->toContain($mode === 'property' ? 'Individual properties' : 'Individual items')
        ->toContain('targetModes: ' . Js::from(['custom' => $mode]))
        ->toContain(
            'updateConfiguration('
                . Js::from([
                    '_my_field' => ['custom' => $mode],
                    'other.name' => ['custom' => $mode],
                ]),
        );
    $changed = $rows;
    $changed[0]['target'] = null;
    $fields[0]->state($changed);
    expect($schema->getState())->toBe(['first' => $changed, 'second' => $rows]);
    $schema->fill($schema->getState());
    expect($schema->getState())->toBe(['first' => $changed, 'second' => $rows]);
})->with(['array items' => ['item', 'array'], 'object properties' => ['property', 'object']]);

it('validates collection connections after reactive source settings change', function (string $mode) {
    $page = Livewire::test(\PHPinnacle\Eidos\Tests\Fixtures\FieldMappingForm::class, ['mappingMode' => $mode])
        ->set('data.mapping.0.target', 'destination')
        ->set('data.mapping.1.target', 'destination')
        ->call('submit')
        ->assertHasNoErrors()
        ->set('data.mapping.0.options.type', 'boolean')
        ->assertSet('data.mapping.0.target', 'destination')
        ->assertSet('data.mapping.1.target', 'destination')
        ->call('submit');

    if ($mode === 'item') {
        $page->assertHasErrors(['data.mapping']);
    } else {
        $page->assertHasNoErrors();
    }

    $page->set('data.mapping.0.options.type', 'string')->call('submit')->assertHasNoErrors();
})->with(['item', 'property']);

it('indexes a large mapping with a single read of its row state', function () {
    $field = new class('mapping') extends FieldMapping {
        public int $stateReads = 0;

        public function getRawState(): mixed
        {
            $this->stateReads++;

            return parent::getRawState();
        }
    };
    $field->configure();
    $properties = [];
    $rows = [];
    for ($index = 0; $index < 64; $index++) {
        $source = 'field_' . $index;
        $properties[$source] = ['type' => 'string'];
        $rows[] = ['kind' => 'source', 'source' => $source, 'target' => null, 'options' => []];
    }

    $field->jsonSchema(source: Schema::fromArray(['properties' => $properties]));
    $schema = FilamentSchema::make(SchemaHost::make())->statePath('data')->components([$field]);
    $schema->fill(['mapping' => array_reverse($rows)]);
    $field->stateReads = 0;
    $indexes = $field->getSourceRowIndexes();

    expect($indexes)
        ->toBe(array_combine(array_keys($properties), range(63, 0)))
        ->and($field->stateReads)
        ->toBe(1);
});

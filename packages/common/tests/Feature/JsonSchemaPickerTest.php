<?php

use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use PHPinnacle\Common\Forms\JsonSchemaPicker;
use PHPinnacle\Common\Infolists\JsonSchemaEntry;
use Tests\TestCase;

uses(TestCase::class);

it('offers clearing only for optional enabled pickers independently of schema required markers', function () {
    [$form] = common_json_schema_form(['totals', 'subtotal']);
    $picker = $form->getComponents()[0]->requiredMarker();
    view()->share('errors', new ViewErrorBag);

    expect($picker->toHtml())
        ->toContain(
            'common-json-schema-picker__clear',
            'Clear selection',
            'schemaValue = null; close()',
            'x-show="schemaValue !== null"',
            'x-on:mousedown.stop',
            'x-on:keyup.enter.stop',
            'x-on:keyup.space.stop',
        );
    expect($form->getState()['selection'])->toBe(['totals', 'subtotal']);

    $required = true;
    $picker->required(function () use (&$required) {
        return $required;
    });
    expect($picker->toHtml())->not->toContain('common-json-schema-picker__clear');

    $required = false;
    expect($picker->toHtml())->toContain('common-json-schema-picker__clear');

    $picker->disabled();
    expect($picker->toHtml())->not->toContain('common-json-schema-picker__clear');
});

it('enables picker search by default and supports dynamic opt-in search for entries', function (int $index) {
    [$form] = common_json_schema_form(null);
    $component = $form->getComponents()[$index];
    view()->share('errors', new ViewErrorBag);

    expect($component->isSearchable())->toBe($index === 0);
    $component->searchable();
    expect($component->toHtml())->toContain('schemaSearchable = true', 'Search fields');

    $searchable = true;
    $component->searchable(function () use (&$searchable) {
        return $searchable;
    });
    expect($component->toHtml())->toContain('schemaSearchable = true');
    $searchable = false;
    expect($component->toHtml())->toContain('schemaSearchable = false');
    expect($component->searchable(false)->isSearchable())->toBeFalse();
    expect($form->getState()['selection'])->toBeNull();
})->with(['picker' => 0, 'entry' => 1]);

it('supports dynamic compact rendering in both adapters', function (int $index) {
    [$form] = common_json_schema_form(null);
    $component = $form->getComponents()[$index];
    view()->share('errors', new ViewErrorBag);

    expect($component->toHtml())->toContain('schemaCompact = false');
    $component->compact();
    expect($component->toHtml())->toContain('schemaCompact = true', 'common-json-schema-tree--compact');

    $compact = true;
    $component->compact(function () use (&$compact) {
        return $compact;
    });
    expect($component->toHtml())->toContain('schemaCompact = true');
    $compact = false;
    expect($component->toHtml())->toContain('schemaCompact = false');
    $component->compact(false);
    expect($component->toHtml())->toContain('schemaCompact = false');
    expect($form->getState()['selection'])->toBeNull();
})->with(['picker' => 0, 'entry' => 1]);

it('keeps required markers and value constraints opt-in without changing picker validation', function (int $index) {
    [$form] = common_json_schema_form(null);
    $component = $form->getComponents()[$index];
    view()->share('errors', new ViewErrorBag);

    expect($component->toHtml())
        ->toContain('schemaRequired = false', 'schemaConstraints = false');

    $component->requiredMarker()->constraints();

    expect($component->toHtml())->toContain('schemaRequired = true', 'schemaConstraints = true');
    expect($form->getComponents()[0]->isRequired())->toBeFalse();
    expect($form->getState()['selection'])->toBeNull();

    $visible = true;
    $component->requiredMarker(function () use (&$visible) {
        return $visible;
    })->constraints(function () use (&$visible) {
        return $visible;
    });

    expect($component->toHtml())->toContain('schemaRequired = true', 'schemaConstraints = true');

    $visible = false;

    expect($component->toHtml())
        ->toContain('schemaRequired = false', 'schemaConstraints = false');

    $component->requiredMarker(false)->constraints(false);

    expect($component->toHtml())
        ->toContain('schemaRequired = false', 'schemaConstraints = false');
})->with(['picker' => 0, 'entry' => 1]);

it('toggles technical names beside permanent titles in both adapters without changing selection', function (int $index) {
    [$form] = common_json_schema_form(['customer', 'email']);
    $component = $form->getComponents()[$index];
    view()->share('errors', new ViewErrorBag);

    expect($component->arePropertiesVisible())->toBeTrue();
    $component->properties(false);
    expect($component->toHtml())->toContain('schemaProperties = false');

    $visible = false;
    $component->properties(function () use (&$visible) {
        return $visible;
    });
    expect($component->arePropertiesVisible())->toBeFalse();
    $visible = true;
    expect($component->toHtml())->toContain('schemaProperties = true');
    expect($component->properties()->arePropertiesVisible())->toBeTrue();
    expect($form->getState()['selection'])->toBe(['customer', 'email']);
})->with(['picker' => 0, 'entry' => 1]);

it('makes readonly branch rows keyboard-accessible disclosure controls while keeping picker actions separate', function () {
    [$form] = common_json_schema_form(null);
    view()->share('errors', new ViewErrorBag);

    $entry = $form->getComponents()[1]->toHtml();
    $picker = $form->getComponents()[0]->toHtml();

    expect($entry)->toContain(
        'common-json-schema-tree__row--expandable',
        "? 'button' : null",
        '? 0 : null',
        'x-on:keydown.enter.prevent=',
        'x-on:keydown.space.prevent=',
        'x-on:click="source.hasChildren && !jsonSchemaSearching && jsonSchemaToggle(source)"',
    );
    expect($entry)->not->toContain('<button', 'jsonSchemaChoose(source)');
    expect($picker)->toContain('jsonSchemaChoose(source)', 'x-on:click="jsonSchemaToggle(source)"');
    expect($picker)->not->toContain('common-json-schema-tree__row--expandable', 'x-on:keydown.space.prevent=');
});

it('renders annotated Type schemas with the same icon registry in picker and entry', function () {
    [$form] = common_json_schema_form(['email']);
    $schema = \PHPinnacle\Common\JsonSchema\Schema::fromArray([
        'type' => 'object',
        'properties' => [
            'email' => [
                'type' => 'string',
                'x-icon' => 'heroicon-o-envelope',
                'x-color' => 'rgba(30, 144, 255, 0.5)',
                'x-display' => ['label' => 'Contact email'],
            ],
        ],
    ]);
    $form->getComponents()[0]->jsonSchema($schema);
    $form->getComponents()[1]->state($schema);
    view()->share('errors', new ViewErrorBag);

    expect($form->getState()['selection'])->toBe(['email']);

    foreach ($form->getComponents() as $component) {
        expect($component->toHtml())
            ->toContain(
                'schemaIcons =',
                '\u003Csvg',
                'heroicon-o-envelope',
                'x-display',
                'source.iconHtml',
                'rgba(30, 144, 255, 0.5)',
                'jsonSchemaColorStyles(source.color)',
                'common-json-schema-tree__field-icon',
            );

        $visible = false;
        $component->icons(function () use (&$visible) {
            return $visible;
        });

        expect($component->toHtml())->not->toContain('\u003Csvg');

        $visible = true;

        expect($component->toHtml())->toContain('\u003Csvg');
        expect($component->icons(false)->toHtml())->not->toContain('\u003Csvg');
        expect($component->icons()->toHtml())->toContain('\u003Csvg');
    }
});

it('toggles schema colors independently of icons in both adapters while preserving selection', function (int $index) {
    [$form] = common_json_schema_form(['customer', 'email']);
    $component = $form->getComponents()[$index];
    view()->share('errors', new ViewErrorBag);

    expect($component->areColorsVisible())->toBeTrue();
    expect($component->toHtml())->toContain('schemaColors = true');
    $component->colors(false);
    expect($component->toHtml())->toContain('schemaColors = false');
    expect($component->areIconsVisible())->toBeTrue();

    $visible = false;
    $component->colors(function () use (&$visible) {
        return $visible;
    });
    expect($component->areColorsVisible())->toBeFalse();
    $visible = true;
    expect($component->toHtml())->toContain('schemaColors = true');
    expect($component->colors()->areColorsVisible())->toBeTrue();
    expect($form->getState()['selection'])->toBe(['customer', 'email']);
})->with(['picker' => 0, 'entry' => 1]);

it('toggles descriptions dynamically in both adapters without changing schema data or selection', function (int $index) {
    [$form] = common_json_schema_form(['customer', 'email']);
    $component = $form->getComponents()[$index];
    $definition = $component->getJsonSchema()->toArray();
    view()->share('errors', new ViewErrorBag);

    expect($component->areDescriptionsVisible())->toBeTrue();
    expect($component->toHtml())->toContain('schemaDescriptions = true');
    $component->descriptions(false);
    expect($component->toHtml())->toContain('schemaDescriptions = false');

    $visible = false;
    $component->descriptions(function () use (&$visible) {
        return $visible;
    });
    expect($component->areDescriptionsVisible())->toBeFalse();
    $visible = true;
    expect($component->toHtml())->toContain('schemaDescriptions = true');
    expect($component->descriptions()->areDescriptionsVisible())->toBeTrue();
    expect($component->getJsonSchema()->toArray())->toBe($definition);
    expect($form->getState()['selection'])->toBe(['customer', 'email']);
})->with(['picker' => 0, 'entry' => 1]);

it('keeps format and enum display opt-in with one dynamic format option', function (int $index) {
    [$form] = common_json_schema_form(null);
    $component = $form->getComponents()[$index];
    view()->share('errors', new ViewErrorBag);

    expect($component->isFormatVisible())->toBeFalse();
    expect($component->toHtml())->toContain('schemaFormat = false');

    $component->format();
    expect($component->toHtml())->toContain('schemaFormat = true');
    $component->format(false);
    expect($component->toHtml())->toContain('schemaFormat = false');

    $format = true;
    $component->format(function () use (&$format) {
        return $format;
    });
    expect($component->toHtml())->toContain('schemaFormat = true');
    $format = false;
    expect($component->toHtml())->toContain('schemaFormat = false');
})->with(['picker' => 0, 'entry' => 1]);

it('keeps native Filament PHP enum configuration independent of schema formats', function () {
    $picker = JsonSchemaPicker::make('selection')
        ->enum(\Filament\Support\Enums\FontFamily::class)
        ->format();
    expect($picker->getEnum())->toBe(\Filament\Support\Enums\FontFamily::class);
    expect($picker->format(false)->getEnum())->toBe(\Filament\Support\Enums\FontFamily::class);

    $picker->enum(fn () => \Filament\Support\Enums\FontFamily::class);
    expect($picker->getEnum())->toBe(\Filament\Support\Enums\FontFamily::class);
    expect($picker->enum(null)->getEnum())->toBeNull();
});

it('evaluates the picker schema once per render', function () {
    [$form] = common_json_schema_form(null);
    $calls = 0;
    $form->getComponents()[0]->jsonSchema(function () use (&$calls) {
        $calls++;

        return JsonSchema::object(['name' => JsonSchema::string()]);
    });
    view()->share('errors', new ViewErrorBag);

    $form->getComponents()[0]->toHtml();

    expect($calls)->toBe(1);
});

it('loads the schema only to validate a nonempty property path', function (mixed $selection, bool $valid, int $calls) {
    [$form] = common_json_schema_form($selection);
    $evaluations = 0;
    $form->getComponents()[0]->jsonSchema(function () use (&$evaluations) {
        $evaluations++;

        return JsonSchema::object(['customer' => JsonSchema::object(['email' => JsonSchema::string()])]);
    });

    if ($valid) {
        expect($form->getState()['selection'])->toBe($selection);
    } else {
        expect($form->getState(...))->toThrow(ValidationException::class);
    }

    expect($evaluations)->toBe($calls);
})->with([
    'empty path' => [[], false, 0],
    'absent' => [null, true, 0],
    'malformed' => ['email', false, 0],
    'nested' => [['customer', 'email'], true, 1],
]);

it('uses Laravel schema objects and closures for selection and readonly rendering', function (bool $useClosure) {
    $selection = ['customer', 'email'];
    [$form] = common_json_schema_form($selection);
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
    $entry->state($useClosure ? fn () => $schema : $schema);
    view()->share('errors', new ViewErrorBag);

    expect($form->getState()['selection'])->toBe($selection);
    expect($picker->toHtml())->toContain('Laravel schema address');
    expect($entry->toHtml())->toContain('Laravel schema address');

    $form->fill(['selection' => ['count']]);

    expect($form->getState()['selection'])->toBe(['count']);
})->with(['direct' => false, 'closure' => true]);

it('validates exact union paths against the current schema closure', function () {
    $selection = ['данные/客户', 'email'];
    [$form] = common_json_schema_form($selection);
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

it('accepts exact schema path segments', function (array $selection) {
    [$form] = common_json_schema_form($selection);

    expect($form->getState()['selection'])->toBe($selection);
})->with([
    'nested' => [['customer', 'email']],
    'literal key' => [['literal.key', 'slash/key']],
    'numeric key' => [['01']],
    'array field' => [['tags']],
    'subtotal' => [['totals', 'subtotal']],
]);

it('defaults to an absent selection', function () {
    [$form] = common_json_schema_form(null);
    $form->fill();

    expect($form->getState()['selection'])->toBeNull();
});

it('does not confuse literal dotted names with nested schema paths', function (bool $nested) {
    $selection = $nested ? ['metadata', 'version'] : ['metadata.version'];
    [$form] = common_json_schema_form($selection);
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
    [$form] = common_json_schema_form($selection);
    $picker = $form->getComponents()[0]
        ->dehydrateStateUsing(fn (?array $state) => $state === null ? null : implode('.', $state));

    expect($form->getState()['selection'])->toBe($selection === null ? null : 'totals.subtotal');
    expect($picker->getState())->toBe($selection);
})->with(['selected' => [['totals', 'subtotal']], 'absent' => [null]]);

it('accepts a field path for a required picker', function () {
    [$form] = common_json_schema_form(['customer', 'email']);
    $form->getComponents()[0]->required();

    expect($form->getState()['selection'])->toBe(['customer', 'email']);
});

it('rejects absent and empty selections for a required picker', function (mixed $selection) {
    [$form] = common_json_schema_form($selection);
    $form->getComponents()[0]->required();

    expect($form->getState(...))->toThrow(ValidationException::class);
})->with(['absent' => [null], 'empty path' => [[]]]);

it('accepts an absent selection for an optional picker', function () {
    [$form] = common_json_schema_form(null);

    expect($form->getState()['selection'])->toBeNull();
});

it('rejects malformed, missing and array item selections', function (mixed $selection) {
    [$form] = common_json_schema_form($selection);

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

it('publishes imported schema modules beside the Alpine components without eager script loading', function () {
    foreach (['json-schema-definition', 'json-schema-presentation'] as $name) {
        $asset = collect(FilamentAsset::getScripts(['phpinnacle/common']))
            ->first(fn ($asset) => $asset->getId() === 'components/' . $name);

        expect($asset)->not->toBeNull();
        expect($asset->getRelativePublicPath())->toBe('js/phpinnacle/common/components/' . $name . '.js');
        expect(is_file($asset->getPath()))->toBeTrue();
        expect($asset->isLoadedOnRequest())->toBeTrue();
        expect($asset->isModule())->toBeTrue();
    }
});

it('renders the same schema tree in the picker and readonly infolist entry', function () {
    [$form] = common_json_schema_form(['customer', 'email']);
    view()->share('errors', new ViewErrorBag);

    $picker = $form->getComponents()[0]->toHtml();
    $entry = $form->getComponents()[1]->toHtml();

    expect($picker)->toContain(
        'common-json-schema-tree',
        'Delivery address',
        'jsonSchemaChoose(source)',
        'schemaValue = source.value',
        'schemaSelectedKey = JSON.stringify(schemaValue)',
        'source.key === schemaSelectedKey',
    );
    expect($entry)->toContain('common-json-schema-tree', 'Delivery address');
    expect($entry)->not->toContain('jsonSchemaChoose(source)');
    expect($picker)
        ->toContain('json-schema-tools.js', 'json-schema-picker.js')
        ->not->toContain('phpinnacle-decima', 'workflow.js');
    expect($entry)
        ->toContain('json-schema-tools.js', 'json-schema-picker.js')
        ->not->toContain('phpinnacle-decima', 'workflow.js');
});

/** @return array{Schema, Component} */
function common_json_schema_form(mixed $selection): array
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
                    JsonSchemaEntry::make('preview')->state($jsonSchema),
                ]);
        }
    };
    $livewire->setId('common-json-schema-test');
    $livewire->setName('common-json-schema-test');
    $form = $livewire->getSchema('form');
    $form->fill(['selection' => $selection]);

    return [$form, $livewire];
}

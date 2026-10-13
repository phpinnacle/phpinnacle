<?php

namespace PHPinnacle\Eidos\Tests\Unit\Services;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Schema;
use PHPinnacle\Eidos\JsonSchema\Schema as JsonSchemaDefinition;
use PHPinnacle\Eidos\Services\SchemaFactory;
use PHPinnacle\Eidos\Services\SchemaIntrospector;
use Tests\TestCase;

uses(TestCase::class);

class GeneratedSchemaProduct extends Model
{
    public $timestamps = false;

    protected $table = 'eidos_schema_products';

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(GeneratedSchemaSupplier::class, 'supplier_id');
    }
}

class GeneratedSchemaSupplier extends Model
{
    public $timestamps = false;

    protected $table = 'eidos_schema_suppliers';

    public function products(): HasMany
    {
        return $this->hasMany(GeneratedSchemaProduct::class, 'supplier_id');
    }
}

class CustomSchemaCast extends AsArrayObject {}

class CustomSchemaRecord extends Model
{
    public $timestamps = false;

    protected $table = 'eidos_cast_records';

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(GeneratedSchemaSupplier::class, 'supplier_id');
    }
}

it('marks stored and virtual generated columns as read only across relations', function () {
    Schema::create('eidos_schema_suppliers', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('display_name')->storedAs('upper(name)');
    });
    Schema::create('eidos_schema_products', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('supplier_id')->nullable();
        $table->integer('quantity');
        $table->integer('stored_total')->storedAs('quantity * 2');
        $table->string('virtual_label')->nullable()->virtualAs("'item-' || quantity");
    });

    $factory = app(SchemaFactory::class);
    $introspector = app(SchemaIntrospector::class);
    $schema = $introspector->introspect(new GeneratedSchemaProduct)->toArray();

    expect($schema['properties']['stored_total'])
        ->toBe(['type' => ['integer', 'null'], 'readOnly' => true])
        ->and($schema['properties']['virtual_label'])
        ->toBe(['type' => ['string', 'null'], 'readOnly' => true])
        ->and($schema['properties']['quantity'])
        ->not
        ->toHaveKey('readOnly')
        ->and($schema['properties']['supplier']['properties']['display_name']['readOnly'])
        ->toBeTrue()
        ->and($factory->create(GeneratedSchemaProduct::class)->toArray())
        ->toBe($schema);

    $supplierProperties = $introspector->introspect(new GeneratedSchemaSupplier)->toArray()['properties'];

    expect($supplierProperties)
        ->toHaveKeys(['name', 'display_name'])
        ->not->toHaveKey('quantity');

    expect($supplierProperties['products']['type'])
        ->toBe('array')
        ->and($supplierProperties['products']['items']['properties']['stored_total']['readOnly'])
        ->toBeTrue()
        ->and($supplierProperties['products']['items']['properties'])
        ->not->toHaveKey('supplier');
});

it('uses registered cast schemas and preserves annotations, nullability and generated fields', function (bool $useSchema) {
    Schema::create('eidos_schema_suppliers', function (Blueprint $table) {
        $table->id();
        $table->string('name');
    });
    Schema::create('eidos_cast_records', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('supplier_id')->nullable();
        $table->text('metadata')->nullable();
        $table->string('label');
        $table->string('generated_label')->storedAs('upper(label)');
    });
    $model = new CustomSchemaRecord;
    $model->mergeCasts(['metadata' => CustomSchemaCast::class, 'generated_label' => CustomSchemaCast::class]);
    $introspector = new SchemaIntrospector;
    $introspector->registerCast(CustomSchemaCast::class, fn () => (
        $useSchema
            ? JsonSchemaDefinition::fromArray([
                'type' => 'object',
                'properties' => ['name' => ['type' => 'string']],
                'x-icon' => 'heroicon-o-user',
            ])
            : JsonSchema::object(['name' => JsonSchema::string()])
    ));

    $properties = new SchemaFactory($introspector)
        ->create($model)
        ->toArray()['properties'];

    expect($properties['metadata']['type'])
        ->toBe(['object', 'null'])
        ->and($properties['metadata']['properties']['name']['type'])
        ->toBe('string')
        ->and($properties['generated_label']['type'])
        ->toBe(['object', 'null'])
        ->and($properties['generated_label']['readOnly'])
        ->toBeTrue()
        ->and($properties['label']['type'])
        ->toBe('string');

    if ($useSchema) {
        expect($properties['metadata']['x-icon'])->toBe('heroicon-o-user');
    }
})->with(['native type' => false, 'annotated schema' => true]);

it('passes model, attribute and cast arguments to handlers for root and related fields', function () {
    Schema::create('eidos_schema_suppliers', function (Blueprint $table) {
        $table->id();
        $table->string('name');
    });
    Schema::create('eidos_cast_records', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('supplier_id')->nullable();
        $table->string('label');
        $table->string('plain_label');
    });
    $model = new CustomSchemaRecord;
    $model->mergeCasts(['label' => CustomSchemaCast::class . ':10,20', 'plain_label' => CustomSchemaCast::class]);
    $introspector = new SchemaIntrospector;
    $introspector->registerCast(CustomSchemaCast::class, fn (
        Model $model,
        string $attribute,
        array $arguments,
    ) => JsonSchema::string()
        ->title($model->getTable() . '.' . $attribute)
        ->enum($arguments === [] ? ['plain'] : $arguments));
    $definition = $introspector->introspect($model)->toArray();

    expect($definition['properties']['label']['title'])
        ->toBe('eidos_cast_records.label')
        ->and($definition['properties']['label']['enum'])
        ->toBe(['10', '20'])
        ->and($definition['properties']['plain_label']['enum'])
        ->toBe(['plain']);

    $introspector->registerCast('int', fn (Model $model, string $attribute, array $arguments) => JsonSchema::integer()
        ->title($model->getTable() . '.' . $attribute));
    $definition = $introspector->introspect($model)->toArray();

    expect($definition['properties']['supplier']['properties']['id']['title'])->toBe('eidos_schema_suppliers.id');
});

it('keeps default cast and database types when no handler is registered', function () {
    Schema::create('eidos_schema_suppliers', function (Blueprint $table) {
        $table->id();
        $table->string('name');
    });
    Schema::create('eidos_cast_records', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('supplier_id')->nullable();
        $table->text('metadata');
        $table->integer('quantity');
    });
    $model = new CustomSchemaRecord;
    $model->mergeCasts(['metadata' => CustomSchemaCast::class]);

    $properties = new SchemaIntrospector()
        ->introspect($model)
        ->toArray()['properties'];

    expect($properties['metadata']['type'])
        ->toBe(['object', 'array'])
        ->and($properties['quantity']['type'])
        ->toBe('integer');
});

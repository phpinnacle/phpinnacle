<?php

namespace PHPinnacle\Eidos\Tests\Unit\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
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

it('marks stored and virtual generated columns as read only across relations and cached schemas', function () {
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

    config()->set('phpinnacle-eidos.models', [GeneratedSchemaProduct::class]);
    $this->artisan('eidos:cache-eloquent-schemas')->assertSuccessful();
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

    $this->artisan('eidos:cache-eloquent-schemas')->assertSuccessful();

    expect($factory->create(GeneratedSchemaProduct::class)->toArray())->toBe($schema);
});

<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Schema as DatabaseSchema;
use PHPinnacle\Eidos\Services\SchemaIntrospector;
use Tests\TestCase;

uses(TestCase::class);

class EidosIntrospectorModel extends Model
{
    public $timestamps = false;

    protected $table = 'eidos_introspector_models';

    protected $casts = ['name' => 'string'];
}

beforeEach(function () {
    DatabaseSchema::create('eidos_introspector_models', function (Blueprint $table) {
        $table->id();
        $table->string('name');
    });
});

it('creates independent schemas for model classes and instances', function (bool $useInstance) {
    $introspector = app(SchemaIntrospector::class);
    $model = $useInstance ? new EidosIntrospectorModel : EidosIntrospectorModel::class;
    $schema = $introspector->introspect($model);
    $definition = $schema->toArray();

    expect($definition['properties']['name']['type'])->toBe('string');

    $schema->title('Changed in this consumer');

    expect($introspector->introspect($model)->toArray())->toBe($definition);
})->with(['model class' => false, 'model instance' => true]);

it('shares cast registrations across container resolutions without sharing generated schemas', function () {
    $introspector = app(SchemaIntrospector::class)->registerCast('string', fn () => JsonSchema::string()->title(
        'Registered cast',
    ));
    $resolved = app(SchemaIntrospector::class);

    expect($resolved)->toBe($introspector);

    $schema = $resolved->introspect(EidosIntrospectorModel::class);
    $definition = $schema->toArray();
    $schema->title('Changed in this consumer');

    expect($definition['properties']['name']['title'])
        ->toBe('Registered cast')
        ->and(app(SchemaIntrospector::class)->introspect(new EidosIntrospectorModel)->toArray())
        ->toBe($definition);
});

it('reflects column changes on the next request', function () {
    $introspector = app(SchemaIntrospector::class);
    $original = $introspector->introspect(EidosIntrospectorModel::class)->toArray();

    DatabaseSchema::table('eidos_introspector_models', function (Blueprint $table) {
        $table->string('description')->nullable();
    });

    $schema = $introspector->introspect(EidosIntrospectorModel::class)->toArray();

    expect($original['properties'])
        ->not
        ->toHaveKey('description')
        ->and($schema['properties']['description']['type'])
        ->toBe(['string', 'null']);
});

it('uses the supplied model instance table', function () {
    DatabaseSchema::create('eidos_alternative_models', function (Blueprint $table) {
        $table->id();
        $table->string('description');
    });
    $model = new EidosIntrospectorModel;
    $model->setTable('eidos_alternative_models');

    $properties = app(SchemaIntrospector::class)->introspect($model)->toArray()['properties'];

    expect($properties)->toHaveKey('description')->not->toHaveKey('name');
});

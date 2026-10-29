<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema as DatabaseSchema;
use PHPinnacle\Eidos\Services\SchemaIntrospector;
use PHPinnacle\Jason\SchemaBuilder;
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

it('applies shared cast registrations to later introspections of model classes and instances', function (bool $useInstance) {
    $introspector = app(SchemaIntrospector::class);
    $model = $useInstance ? new EidosIntrospectorModel : EidosIntrospectorModel::class;
    $schema = $introspector->introspect($model);
    $introspector->registerCast('string', fn () => SchemaBuilder::make()
        ->type('string')
        ->title('Registered cast')
        ->build());
    $nextSchema = app(SchemaIntrospector::class)->introspect($model);

    expect($schema->properties()['name']->type())
        ->toBe('string')
        ->and($schema->properties()['name']->title())
        ->toBeNull()
        ->and($nextSchema->properties()['name']->type())
        ->toBe('string')
        ->and($nextSchema->properties()['name']->title())
        ->toBe('Registered cast');
})->with(['model class' => false, 'model instance' => true]);

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

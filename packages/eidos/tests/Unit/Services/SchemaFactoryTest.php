<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema as DatabaseSchema;
use PHPinnacle\Eidos\Services\SchemaFactory;
use Tests\TestCase;

uses(TestCase::class);

class EidosFactoryModel extends Model
{
    public $timestamps = false;

    protected $table = 'eidos_factory_models';
}

beforeEach(function () {
    DatabaseSchema::create('eidos_factory_models', function (Blueprint $table) {
        $table->id();
        $table->string('name');
    });
});

it('creates independent schemas for model classes and instances', function (bool $useInstance) {
    $factory = app(SchemaFactory::class);
    $model = $useInstance ? new EidosFactoryModel : EidosFactoryModel::class;
    $schema = $factory->create($model);
    $definition = $schema->toArray();

    expect($definition['properties']['name']['type'])->toBe('string');

    $schema->title('Changed in this consumer');

    expect($factory->create($model)->toArray())->toBe($definition);
})->with(['model class' => false, 'model instance' => true]);

it('reflects column changes on the next request', function () {
    $factory = app(SchemaFactory::class);
    $original = $factory->create(EidosFactoryModel::class)->toArray();

    DatabaseSchema::table('eidos_factory_models', function (Blueprint $table) {
        $table->string('description')->nullable();
    });

    $schema = $factory->create(EidosFactoryModel::class)->toArray();

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
    $model = new EidosFactoryModel;
    $model->setTable('eidos_alternative_models');

    $properties = app(SchemaFactory::class)->create($model)->toArray()['properties'];

    expect($properties)->toHaveKey('description')->not->toHaveKey('name');
});

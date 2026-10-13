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

it('creates and caches schemas on demand for model classes and instances', function (bool $useInstance) {
    $factory = app(SchemaFactory::class);
    $model = $useInstance ? new EidosFactoryModel : EidosFactoryModel::class;
    $schema = $factory->create($model);
    $definition = $schema->toArray();

    expect($definition['properties']['name']['type'])->toBe('string');

    DatabaseSchema::drop('eidos_factory_models');
    $schema->title('Changed in this consumer');

    expect(app(SchemaFactory::class)->create(EidosFactoryModel::class)->toArray())
        ->toBe($definition)
        ->and($factory->create(new EidosFactoryModel)->toArray())
        ->toBe($definition);
})->with(['model class' => false, 'model instance' => true]);

it('warms explicitly configured models once and refreshes cached schemas', function () {
    config()->set('phpinnacle-eidos.models', [EidosFactoryModel::class, EidosFactoryModel::class]);
    config()->set('phpinnacle-eidos.discovery.except', [EidosFactoryModel::class]);
    $factory = app(SchemaFactory::class);
    $original = $factory->create(EidosFactoryModel::class)->toArray();

    DatabaseSchema::table('eidos_factory_models', function (Blueprint $table) {
        $table->string('description')->nullable();
    });

    expect($factory->create(EidosFactoryModel::class)->toArray())->toBe($original);

    $this
        ->artisan('eidos:cache-eloquent-schemas')
        ->expectsOutputToContain('Cached schemas for 1 Eloquent model(s).')
        ->assertSuccessful();

    $refreshed = $factory->create(EidosFactoryModel::class)->toArray();
    expect($refreshed['properties']['description']['type'])->toBe(['string', 'null']);

    DatabaseSchema::drop('eidos_factory_models');
    config()->set('phpinnacle-eidos.models', []);

    $this
        ->artisan('eidos:cache-eloquent-schemas')
        ->expectsOutputToContain('Cached schemas for 0 Eloquent model(s).')
        ->assertSuccessful();

    expect($factory->create(EidosFactoryModel::class)->toArray())->toBe($refreshed);
});

it('warms and reads schemas in the configured cache store without changing the default store', function () {
    $defaultFactory = app(SchemaFactory::class);
    $original = $defaultFactory->create(EidosFactoryModel::class)->toArray();
    DatabaseSchema::table('eidos_factory_models', function (Blueprint $table) {
        $table->string('description');
    });
    config()->set('cache.stores.eidos', ['driver' => 'array', 'serialize' => false]);
    config()->set('phpinnacle-eidos.cache.store', 'eidos');
    $factory = app(SchemaFactory::class);

    expect($factory->warmup([EidosFactoryModel::class, EidosFactoryModel::class], []))->toBe(1);
    DatabaseSchema::drop('eidos_factory_models');

    expect(app(SchemaFactory::class)->create(EidosFactoryModel::class)->toArray()['properties'])
        ->toHaveKey('description')
        ->and($defaultFactory->create(EidosFactoryModel::class)->toArray())
        ->toBe($original);
});

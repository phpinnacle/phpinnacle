<?php

use Composer\Autoload\ClassLoader;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PHPinnacle\Eidos\Services\ModelDiscovery;
use PHPinnacle\Eidos\Services\SchemaFactory;
use PHPinnacle\Eidos\Tests\Fixtures\Discovery\Product;
use Tests\TestCase;

uses(TestCase::class);

it('discovers unloaded PSR-4 models and applies include and except patterns during warmup', function () {
    $namespace = 'PHPinnacle\\Eidos\\Tests\\Fixtures\\Discovery\\';
    $directory = __DIR__ . '/../../Fixtures/Discovery';
    $loader = new ClassLoader($directory);
    $loader->addPsr4($namespace, [$directory . '/missing', $directory]);
    $loader->register();

    try {
        expect(class_exists(Product::class, false))->toBeFalse();
        expect(new ModelDiscovery()->modelClasses([$namespace . '*'], [$namespace . 'Excluded\\*']))
            ->toBe([Product::class]);
        Schema::create('eidos_discovered_products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        config()->set('phpinnacle-eidos.models', [Product::class]);
        config()->set('phpinnacle-eidos.discovery.include', [$namespace . '*']);
        config()->set('phpinnacle-eidos.discovery.except', [$namespace . 'Excluded\\*']);

        expect(app(SchemaFactory::class)->warmup([$namespace . '*'], [$namespace . 'Excluded\\*']))->toBe(1);

        $this
            ->artisan('eidos:cache-eloquent-schemas')
            ->expectsOutputToContain('Cached schemas for 1 Eloquent model(s).')
            ->assertSuccessful();

        Schema::drop('eidos_discovered_products');
        $factory = app(SchemaFactory::class);

        expect($factory->create(Product::class)->toArray()['properties']['name']['type'])
            ->toBe('string');
    } finally {
        $loader->unregister();
    }
});

it('disables discovery when no include patterns are configured', function () {
    expect(new ModelDiscovery()->modelClasses([], []))->toBe([]);
});

it('keeps explicit model classes without discovery even when they match exclusions', function () {
    expect(new ModelDiscovery()->modelClasses([Product::class, Product::class], [
        'PHPinnacle\\Eidos\\Tests\\Fixtures\\*',
    ]))
        ->toBe([Product::class]);
});

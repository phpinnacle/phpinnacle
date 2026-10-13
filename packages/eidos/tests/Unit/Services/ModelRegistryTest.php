<?php

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use PHPinnacle\Eidos\Contracts\ProvidesEloquentModels;
use PHPinnacle\Eidos\Services\ModelRegistry;

it('combines configured sources into a unique model catalog', function () {
    $source = new class implements ProvidesEloquentModels {
        public function modelClasses(): array
        {
            return [Model::class, Model::class];
        }
    };
    $container = new Container;
    $container->instance($source::class, $source);
    $registry = new ModelRegistry($container, [$source::class, $source::class]);

    expect($registry->modelClasses())->toBe([Model::class]);
    expect($registry->all())->toBe([$source::class => $source]);
});

it('accepts an empty model catalog', function () {
    $registry = new ModelRegistry(new Container, []);

    expect($registry->modelClasses())->toBe([]);
    expect($registry->all())->toBe([]);
});

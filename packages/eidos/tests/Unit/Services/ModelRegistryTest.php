<?php

use Illuminate\Database\Eloquent\Model;
use PHPinnacle\Eidos\Contracts\ProvidesEloquentModels;
use PHPinnacle\Eidos\Services\ModelRegistry;
use Tests\TestCase;

uses(TestCase::class);

final readonly class EidosRegistryModels
{
    /** @param list<class-string<Model>> $models */
    public function __construct(
        public array $models,
    ) {}
}

final class EidosRegistrySource implements ProvidesEloquentModels
{
    public function __construct(
        private readonly EidosRegistryModels $models,
    ) {}

    public function modelClasses(): array
    {
        return $this->models->models;
    }
}

it('combines configured sources into a unique model catalog', function () {
    $source = new class implements ProvidesEloquentModels {
        public function modelClasses(): array
        {
            return [Model::class, Model::class];
        }
    };
    $registry = new ModelRegistry([$source::class => $source]);

    expect($registry->modelClasses())->toBe([Model::class]);
    expect($registry->all())->toBe([$source::class => $source]);
});

it('accepts an empty model catalog', function () {
    $registry = new ModelRegistry([]);

    expect($registry->modelClasses())->toBe([]);
    expect($registry->all())->toBe([]);
});

it('resolves configured source dependencies and shares the registry across consumers', function () {
    config()->set('phpinnacle-eidos.model_sources', [EidosRegistrySource::class, EidosRegistrySource::class]);
    app()->instance(EidosRegistryModels::class, new EidosRegistryModels([Model::class, Model::class]));

    $registry = app(ModelRegistry::class);

    expect($registry->modelClasses())
        ->toBe([Model::class])
        ->and(array_keys($registry->all()))
        ->toBe([EidosRegistrySource::class])
        ->and(app(ModelRegistry::class))
        ->toBe($registry);
});

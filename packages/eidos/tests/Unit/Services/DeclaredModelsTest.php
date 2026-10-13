<?php

namespace PHPinnacle\Eidos\Tests\Unit\Services;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use PHPinnacle\Eidos\Services\DeclaredModels;

it('discovers instantiable models and excludes base models and configured prefixes', function () {
    $discovered = new DeclaredModels()->modelClasses();
    $excluded = new DeclaredModels([__NAMESPACE__])->modelClasses();

    expect($discovered)
        ->toContain(DeclaredTestModel::class)
        ->not->toContain(AbstractTestModel::class, Model::class, EloquentModel::class);
    expect($excluded)->not->toContain(DeclaredTestModel::class);
});

abstract class AbstractTestModel extends EloquentModel {}

class Model extends EloquentModel {}

class DeclaredTestModel extends Model {}

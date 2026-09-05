<?php

use PHPinnacle\Casus\Models\Exception as ExceptionRecord;
use PHPinnacle\Casus\Resources\Exceptions\ExceptionResource;
use Tests\TestCase;

uses(TestCase::class);

it('preserves nullable connection and navigation configuration', function () {
    config()->set('phpinnacle-casus.connection', null);
    config()->set('phpinnacle-casus.navigation.exception', ['icon' => null, 'sort' => null]);

    $model = new ExceptionRecord;
    $model->setConnection('model-default');

    expect($model->getConnectionName())
        ->toBeNull()
        ->and(ExceptionResource::getNavigationIcon())
        ->toBeNull()
        ->and(ExceptionResource::getNavigationSort())
        ->toBeNull();

    config()->set('phpinnacle-casus.connection', 'casus');
    config()->set('phpinnacle-casus.navigation.exception', ['icon' => 'phosphor-warning', 'sort' => 0]);

    expect($model->getConnectionName())
        ->toBe('casus')
        ->and(ExceptionResource::getNavigationIcon())
        ->toBe('phosphor-warning')
        ->and(ExceptionResource::getNavigationSort())
        ->toBe(0);

    config()->set('phpinnacle-casus', []);

    expect($model->getConnectionName())
        ->toBe('model-default')
        ->and(ExceptionResource::getNavigationIcon())
        ->toBe('phosphor-bug');
});

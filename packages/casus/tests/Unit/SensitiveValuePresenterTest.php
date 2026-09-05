<?php

use PHPinnacle\Casus\Support\SensitiveValuePresenter;
use Tests\TestCase;

uses(TestCase::class);

it('redacts configured keys recursively and flattens displayed values', function () {
    config()->set('phpinnacle-casus.redaction', [
        'replacement' => '[redacted]',
        'keys' => ['authorization', 'password'],
    ]);

    $presenter = new SensitiveValuePresenter;

    expect($presenter->redact([
        'profile' => ['password_confirmation' => 'secret', 'name' => 'Ada'],
    ]))
        ->toBe([
            'profile' => ['password_confirmation' => '[redacted]', 'name' => 'Ada'],
        ])
        ->and($presenter->present([
            'authorization' => ['Bearer secret'],
            'accept' => ['application/json', 'text/plain'],
        ]))
        ->toBe([
            'authorization' => '[redacted]',
            'accept' => 'application/json, text/plain',
        ]);
});

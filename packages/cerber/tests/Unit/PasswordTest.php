<?php

use PHPinnacle\Cerber\Models\Password;

afterEach(function () {
    Password::alphabet('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');
});

it('generates numeric passwords with the requested length', function () {
    Password::numeric();

    $password = Password::generate(8);

    expect($password)->toHaveLength(8)->and($password)->toMatch('/^[0-9]+$/');
});

it('generates alphabetic passwords', function () {
    Password::alpha();

    expect(Password::generate(12))
        ->toHaveLength(12)
        ->toMatch('/^[a-zA-Z]+$/');
});

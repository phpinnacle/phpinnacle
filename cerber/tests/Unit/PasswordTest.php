<?php

use PHPinnacle\Cerber\Models\Password;

afterEach(function (): void {
    Password::alphabet('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');
});

it('generates numeric passwords with the requested length', function (): void {
    Password::numeric();

    $password = Password::generate(8);

    expect($password)->toHaveLength(8)
        ->and($password)->toMatch('/^[0-9]+$/');
});

it('generates alphabetic passwords', function (): void {
    Password::alpha();

    expect(Password::generate(12))
        ->toHaveLength(12)
        ->toMatch('/^[a-zA-Z]+$/');
});

<?php

use PHPinnacle\Casus\Enums\HttpMethod;
use PHPinnacle\Casus\Models\Exception as ExceptionRecord;

it('provides labels and colors for HTTP methods', function (HttpMethod $method, string $color) {
    expect($method->getLabel())->toBe($method->value)->and($method->getColor())->toBe($color);
})->with([
    'get' => [HttpMethod::Get, 'success'],
    'post' => [HttpMethod::Post, 'info'],
    'put' => [HttpMethod::Put, 'warning'],
    'patch' => [HttpMethod::Patch, 'warning'],
    'delete' => [HttpMethod::Delete, 'danger'],
]);

it('builds a source link from an exception record', function () {
    $exception = new ExceptionRecord;
    $exception->file = 'src/Example.php';
    $exception->line = '42';

    expect($exception->link())->toBe('src/Example.php:42');
});

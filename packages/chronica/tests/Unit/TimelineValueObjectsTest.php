<?php

use PHPinnacle\Chronica\Timeline\Attribute;
use PHPinnacle\Chronica\Timeline\Relation;

it('configures a timeline attribute', function () {
    $attribute = Attribute::make('status')
        ->label('Order status')
        ->formatter(fn (?string $value) => strtoupper($value ?? ''));

    expect($attribute->name)
        ->toBe('status')
        ->and($attribute->label)
        ->toBe('Order status')
        ->and(($attribute->formatter)('pending'))
        ->toBe('PENDING');
});

it('configures a timeline relation', function () {
    $relation = Relation::make('subject', stdClass::class)
        ->label('Subject')
        ->title('name');

    expect($relation->name)
        ->toBe('subject')
        ->and($relation->class)
        ->toBe(stdClass::class)
        ->and($relation->label)
        ->toBe('Subject')
        ->and($relation->title)
        ->toBe('name');
});

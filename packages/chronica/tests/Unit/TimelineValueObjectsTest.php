<?php

use Carbon\CarbonImmutable;
use Filament\Schemas\Schema;
use PHPinnacle\Chronica\Timeline\Attribute;
use PHPinnacle\Chronica\Timeline\Relation;
use Tests\TestCase;

uses(TestCase::class);

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

it('formats temporal attributes with Filament defaults or a custom format', function () {
    $value = CarbonImmutable::parse('1979-07-14 16:30:45');
    $schema = new Schema()->configure();

    expect((Attribute::make('date')->date()->formatter)($value))
        ->toBe($value->translatedFormat($schema->getDefaultDateDisplayFormat()))
        ->and((Attribute::make('date')->date('d.m.Y')->formatter)($value))
        ->toBe('14.07.1979')
        ->and((Attribute::make('datetime')->datetime('d.m.Y H:i')->formatter)($value))
        ->toBe('14.07.1979 16:30')
        ->and((Attribute::make('time')->time('H:i')->formatter)($value))
        ->toBe('16:30');
});

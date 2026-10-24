<?php

namespace PHPinnacle\Franz\Tests\Unit;

use PHPinnacle\Franz\Support\ResponseValue;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

final class ResponseValueTest extends TestCase
{
    #[DataProvider('validValues')]
    public function test_null_is_opt_in_and_valid_values_are_preserved(string $method, mixed $value): void
    {
        self::assertSame($value, ResponseValue::$method($value));
        self::assertSame($value, ResponseValue::$method($value, nullable: true));
        self::assertNull(ResponseValue::$method(null, nullable: true));

        $this->expectException(UnexpectedValueException::class);
        ResponseValue::$method(null);
    }

    public static function validValues(): iterable
    {
        yield 'string' => ['string', ''];
        yield 'bool' => ['bool', false];
        yield 'int' => ['int', 0];
        yield 'object' => ['object', ['topic' => 'lessons']];
        yield 'objects' => ['objects', [['topic' => 'lessons']]];
        yield 'items' => ['items', [null, 0, false]];
        yield 'strings' => ['strings', ['lessons', '']];
        yield 'config' => ['config', ['tasks.max' => '1']];
    }

    #[DataProvider('invalidValues')]
    public function test_nullable_does_not_accept_other_invalid_values(string $method, mixed $value): void
    {
        $this->expectException(UnexpectedValueException::class);
        ResponseValue::$method($value, nullable: true);
    }

    public static function invalidValues(): iterable
    {
        yield 'string' => ['string', 42];
        yield 'bool' => ['bool', 0];
        yield 'int' => ['int', '1'];
        yield 'object' => ['object', 'object'];
        yield 'objects' => ['objects', [null]];
        yield 'items' => ['items', ['topic' => 'lessons']];
        yield 'strings' => ['strings', [null]];
        yield 'config' => ['config', ['tasks.max' => null]];
    }
}

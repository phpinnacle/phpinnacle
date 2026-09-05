<?php

namespace PHPinnacle\Rosetta\Tests;

use PHPinnacle\Rosetta\SerializedDataParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class SerializedDataParserTest extends TestCase
{
    #[Test]
    public function it_parses_the_1c_text_serialization(): void
    {
        $input = "\xEF\xBB\xBF{3,{1,0,fc59acc3-f1f7-4e3f-96da-e580f2c5a88f},\"Текст с \"\"кавычками\"\"\",-2}";

        $this->assertSame(
            [
                3,
                [1, 0, 'fc59acc3-f1f7-4e3f-96da-e580f2c5a88f'],
                'Текст с "кавычками"',
                -2,
            ],
            new SerializedDataParser()->parse($input),
        );
    }

    #[Test]
    #[DataProvider('invalidSerializations')]
    public function it_rejects_invalid_serialization(string $input, string $message): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($message);

        new SerializedDataParser()->parse($input);
    }

    /** @return iterable<string, array{string, string}> */
    public static function invalidSerializations(): iterable
    {
        yield 'unterminated string' => ['{"value}', 'Unterminated string'];
        yield 'trailing data' => ['{1} trailing', 'Unexpected data after the root object'];
        yield 'missing root object' => ['', 'Expected "{"'];
    }
}

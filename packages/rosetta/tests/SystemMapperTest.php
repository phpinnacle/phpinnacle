<?php

namespace PHPinnacle\Rosetta\Tests;

require_once __DIR__ . '/Fixtures/MetadataFixture.php';

use PHPinnacle\Rosetta\Enums\MetadataKind;
use PHPinnacle\Rosetta\SystemMapper;
use PHPinnacle\Rosetta\Tests\Fixtures\MetadataFixture;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class SystemMapperTest extends TestCase
{
    /** @param array<string, int|bool|string> $expected */
    #[Test]
    #[DataProvider('systemFields')]
    public function it_reads_the_configured_type_and_allowed_length(
        MetadataKind $kind,
        int $type,
        int $length,
        int $allowedLength,
        array $expected,
    ): void {
        $fields = new SystemMapper()->map(self::definition($kind, $type, $length, $allowedLength), $kind, 'object-id');
        $name = $kind === MetadataKind::Reference ? '_code' : '_number';

        $this->assertSame($expected, $fields[$name]->jsonSerialize());
    }

    /** @return iterable<string, array{MetadataKind, int, int, int, array<string, int|bool|string>}> */
    public static function systemFields(): iterable
    {
        yield 'numeric code' => [
            MetadataKind::Reference,
            0,
            9,
            1,
            ['type' => 'number', 'precision' => 9, 'scale' => 0, 'unsigned' => true],
        ];
        yield 'wide numeric code' => [
            MetadataKind::Reference,
            0,
            30,
            1,
            ['type' => 'number', 'precision' => 30, 'scale' => 0, 'unsigned' => true],
        ];
        yield 'variable code' => [
            MetadataKind::Reference,
            1,
            5,
            1,
            ['type' => 'string', 'length' => 5, 'fixed' => false],
        ];
        yield 'fixed code' => [
            MetadataKind::Reference,
            1,
            11,
            0,
            ['type' => 'string', 'length' => 11, 'fixed' => true],
        ];
        yield 'numeric number' => [
            MetadataKind::Document,
            0,
            9,
            1,
            ['type' => 'number', 'precision' => 9, 'scale' => 0, 'unsigned' => true],
        ];
        yield 'variable number' => [
            MetadataKind::Document,
            1,
            11,
            1,
            ['type' => 'string', 'length' => 11, 'fixed' => false],
        ];
        yield 'fixed number' => [
            MetadataKind::Document,
            1,
            11,
            0,
            ['type' => 'string', 'length' => 11, 'fixed' => true],
        ];
    }

    #[Test]
    #[DataProvider('invalidSettings')]
    public function it_rejects_invalid_system_field_settings(
        MetadataKind $kind,
        mixed $type,
        mixed $allowedLength,
    ): void {
        $this->expectException(RuntimeException::class);
        new SystemMapper()->map(self::definition($kind, $type, 9, $allowedLength), $kind, 'object-id');
    }

    /** @return iterable<string, array{MetadataKind, mixed, mixed}> */
    public static function invalidSettings(): iterable
    {
        yield 'unknown code type' => [MetadataKind::Reference, 2, 1];
        yield 'missing number type' => [MetadataKind::Document, null, 1];
        yield 'invalid code allowed length' => [MetadataKind::Reference, 1, 2];
        yield 'invalid number allowed length' => [MetadataKind::Document, 1, '1'];
    }

    /** @return array<int, mixed> */
    private static function definition(MetadataKind $kind, mixed $type, int $length, mixed $allowedLength): array
    {
        if ($kind === MetadataKind::Reference) {
            $data = MetadataFixture::structure();
            $data[1][17] = $length;
            $data[1][18] = $type;
            $data[1][51] = $allowedLength;
        } else {
            $data = MetadataFixture::documentWithTabularSection();
            $data[1][11] = $type;
            $data[1][12] = $length;
            $data[1][44] = $allowedLength;
        }

        return $data;
    }
}

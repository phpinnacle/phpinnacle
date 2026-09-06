<?php

namespace PHPinnacle\Porta\Tests\Unit;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use PHPinnacle\Porta\Enums\TransformType;
use PHPinnacle\Porta\Models\Integration;
use PHPinnacle\Porta\Models\Transform;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TransformTest extends TestCase
{
    #[DataProvider('transforms')]
    public function test_it_transforms_selected_payload_values(
        TransformType $type,
        string $path,
        array $config,
        array $payload,
        array $expected,
    ): void {
        $transform = new Transform($path, $type, $config);

        $this->assertSame($expected, $transform->apply($payload));
        $this->assertSame($expected, $type->transform($payload, $path, $config));
    }

    public static function transforms(): iterable
    {
        yield 'map preserves scalar types and leaves objects alone' => [
            TransformType::MAP,
            '$.items[*]',
            ['fields' => ['1' => '2', '*' => '3']],
            ['items' => [1, '1', 'unknown', ['id' => 1]]],
            ['items' => [2, '2', '3', ['id' => 1]]],
        ];
        yield 'drop from selected objects' => [
            TransformType::DROP,
            '$.items[*].secret',
            [],
            ['items' => [['id' => 1, 'secret' => 'a'], ['id' => 2, 'secret' => 'b']]],
            ['items' => [['id' => 1], ['id' => 2]]],
        ];
        yield 'move replaces the destination and removes the source' => [
            TransformType::MOVE,
            '$.source',
            ['dest' => '$.destination'],
            ['source' => ['id' => 1], 'destination' => 'old'],
            ['destination' => ['id' => 1]],
        ];
        yield 'cast selected values' => [
            TransformType::CAST,
            '$.items[*]',
            ['type' => 'integer'],
            ['items' => ['12', '0']],
            ['items' => [12, 0]],
        ];
        yield 'trim only strings' => [
            TransformType::TRIM,
            '$.items[*]',
            [],
            ['items' => [" a \n", 12, null]],
            ['items' => ['a', 12, null]],
        ];
        yield 'trim custom characters' => [
            TransformType::TRIM,
            '$.value',
            ['chars' => '.'],
            ['value' => '..a..'],
            ['value' => 'a'],
        ];
        yield 'rename overwrites existing fields' => [
            TransformType::RENAME,
            '$.items[*]',
            ['fields' => ['old' => 'name']],
            ['items' => [['old' => 'new', 'name' => 'previous'], ['id' => 2], null]],
            ['items' => [['name' => 'new'], ['id' => 2], null]],
        ];
        yield 'insert at the root' => [
            TransformType::INSERT,
            '$',
            ['fields' => ['new' => 1, 'old' => null]],
            ['old' => 'value'],
            ['old' => null, 'new' => 1],
        ];
        yield 'format and modify dates' => [
            TransformType::DATE,
            '$.items[*]',
            [
                'input' => '!d.m.Y',
                'output' => 'Y-m-d',
                'modify' => '+1 day',
                'locale' => 'en',
            ],
            ['items' => ['14.07.1979', null, 12]],
            ['items' => ['1979-07-15', null, 12]],
        ];
        yield 'parse localized dates' => [
            TransformType::DATE,
            '$.date',
            ['output' => 'Y-m-d', 'locale' => 'fr'],
            ['date' => '14 juillet 1979'],
            ['date' => '1979-07-14'],
        ];
        yield 'skip unparseable dates' => [
            TransformType::DATE,
            '$.date',
            ['behavior' => 'skip', 'locale' => 'en'],
            ['date' => 'not a date'],
            ['date' => 'not a date'],
        ];
        yield 'clear unparseable dates' => [
            TransformType::DATE,
            '$.date',
            ['behavior' => 'null', 'locale' => 'en'],
            ['date' => 'not a date'],
            ['date' => null],
        ];
    }

    #[DataProvider('types')]
    public function test_it_leaves_payloads_unchanged_when_the_path_is_absent(TransformType $type): void
    {
        $payload = ['value' => 'untouched'];

        $this->assertSame($payload, new Transform('$.missing.field', $type)->apply($payload));
    }

    public static function types(): iterable
    {
        foreach (TransformType::cases() as $type) {
            yield $type->value => [$type];
        }
    }

    public function test_it_uses_the_clock_for_date_fallbacks_and_future_dates(): void
    {
        Date::setTestNow(CarbonImmutable::parse('2026-09-06 12:00:00'));

        try {
            $fallback = new Transform('$.date', TransformType::DATE, [
                'behavior' => 'now',
                'output' => 'Y-m-d',
                'locale' => 'en',
            ]);
            $future = new Transform('$.dates[*]', TransformType::DATE, [
                'future' => true,
                'output' => 'Y-m-d',
                'locale' => 'en',
            ]);

            $this->assertSame(['date' => '2026-09-06'], $fallback->apply(['date' => 'not a date']));
            $this->assertSame(
                ['dates' => ['2027-01-01', '2026-12-01']],
                $future->apply(['dates' => ['2026-01-01', '2026-12-01']]),
            );
        } finally {
            Date::setTestNow();
        }
    }

    public function test_it_applies_integration_transforms_in_the_configured_order(): void
    {
        $integration = new Integration;
        $integration->transforms = [
            ['type' => 'trim', 'path' => 'name'],
            ['type' => 'map', 'path' => 'name', 'config' => ['fields' => ['Alice' => 'Bob']]],
            ['type' => 'rename', 'config' => ['fields' => ['name' => 'display_name']]],
        ];

        $this->assertSame(['display_name' => 'Bob'], $integration->handle('{"name":" Alice "}'));
    }
}

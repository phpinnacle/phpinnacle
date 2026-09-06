<?php

namespace PHPinnacle\Sequentia;

use DateTimeInterface;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/** @internal */
final readonly class CounterStore
{
    private const array COUNTERS = [
        'SA' => ['bucketed' => true, 'format' => '0'],
        'SY' => ['bucketed' => true, 'format' => 'Y'],
        'SM' => ['bucketed' => true, 'format' => 'Y-m'],
        'GA' => ['bucketed' => false, 'format' => '0'],
        'GY' => ['bucketed' => false, 'format' => 'Y'],
        'GM' => ['bucketed' => false, 'format' => 'Y-m'],
    ];

    public function __construct(
        private Connection $connection,
    ) {}

    /**
     * @param array<string, mixed> $bucket
     * @return array<string, array{tenant: string, scope: string, hash: string, key: string, value: int}>
     */
    public function rows(string $scope, DateTimeInterface $date, string $tenant, array $bucket): array
    {
        $rows = [];

        foreach (self::counters($date, $bucket) as $key => $hash) {
            $identity = compact('tenant', 'scope', 'hash', 'key');
            $rows[serialize($identity)] = [...$identity, 'value' => 1];
        }

        return $rows;
    }

    /**
     * @param array<string, array{tenant: string, scope: string, hash: string, key: string, value: int}> $rows
     */
    public function increment(array $rows): void
    {
        $this->connection->table('sequences')->upsert(
            array_values($rows),
            ['tenant', 'scope', 'hash', 'key'],
            ['value' => $this->connection->raw('sequences.value + 1')],
        );
    }

    /**
     * @param array<string, array{tenant: string, scope: string, hash: string, key: string, value: int}> $rows
     */
    public function replace(array $rows): void
    {
        $this->connection->transaction(function () use ($rows) {
            foreach (array_chunk(array_values($rows), 100) as $chunk) {
                $this->connection->table('sequences')->upsert(
                    $chunk,
                    ['tenant', 'scope', 'hash', 'key'],
                    ['value'],
                );
            }
        });
    }

    /**
     * @param array<string, mixed> $bucket
     * @return array<string, int>
     */
    public function load(string $scope, DateTimeInterface $date, ?string $tenant, array $bucket = []): array
    {
        /** @var array<string, int|numeric-string> $rows */
        $rows = $this->connection
            ->table('sequences')
            ->where([
                'tenant' => $tenant ?? SequenceWatcher::DEFAULT_TENANT,
                'scope' => $scope,
            ])
            ->whereIn('hash', array_values(self::counters($date, $bucket)))
            ->pluck('value', 'key')
            ->all();

        return array_map(
            fn (int|string $value) => (int) $value + 1,
            array_replace(array_fill_keys(array_keys(self::COUNTERS), 0), $rows),
        );
    }

    /**
     * @param array<string, mixed> $bucket
     * @return array<string, string>
     */
    private static function counters(DateTimeInterface $date, array $bucket): array
    {
        $result = [];

        foreach (self::COUNTERS as $key => $counter) {
            $counterBucket = $counter['bucketed'] ? $bucket : [];

            $result[$key] = sha1(serialize([
                'bucket' => $counterBucket,
                'period' => $date->format($counter['format']),
            ]));
        }

        return $result;
    }

    public static function configured(): self
    {
        return new self(DB::connection(
            config('phpinnacle-sequentia.connection') === null
                ? null
                : Config::string('phpinnacle-sequentia.connection'),
        ));
    }
}

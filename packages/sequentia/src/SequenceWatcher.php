<?php

namespace PHPinnacle\Sequentia;

use DateTimeInterface;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class SequenceWatcher
{
    public const string DEFAULT_TENANT = '00000000-0000-0000-0000-000000000000';

    private const array COUNTERS = [
        'SA' => ['bucketed' => true, 'format' => '0'],
        'SY' => ['bucketed' => true, 'format' => 'Y'],
        'SM' => ['bucketed' => true, 'format' => 'Y-m'],
        'GA' => ['bucketed' => false, 'format' => '0'],
        'GY' => ['bucketed' => false, 'format' => 'Y'],
        'GM' => ['bucketed' => false, 'format' => 'Y-m'],
    ];

    private static array $registered = [];

    private static array $watched = [];

    public static function rebuild(): void
    {
        foreach (self::$registered as $scope => $schemes) {
            if (!is_subclass_of($scope, Model::class)) {
                continue;
            }

            $now = now();

            foreach ($scope::query()->lazy() as $record) {
                $date = $record->getCreatedAtColumn() !== null
                    ? $record->getAttributeValue($record->getCreatedAtColumn())
                    : $now;

                foreach ($schemes as $scheme) {
                    self::store($record, $date, $scheme['tenant'], $scheme['bucket']);
                }
            }
        }
    }

    public static function register(string $scope, ?string $tenant = null, array $scheme = []): void
    {
        $key = sha1(serialize($scheme));

        self::$registered[$scope][$key] = [
            'tenant' => $tenant,
            'bucket' => $scheme,
        ];

        self::watch($scope);
    }

    public static function store(Model $record, DateTimeInterface $date, ?string $tenant, array $scheme = []): void
    {
        $scope = $record::class;
        $tenant = $tenant !== null ? $record->getAttributeValue($tenant) : self::DEFAULT_TENANT;
        $bucket = array_combine(
            $scheme,
            array_map($record->getAttributeValue(...), $scheme),
        );

        $counters = self::counters($date, $bucket);
        $bindings = [];
        $values = implode(',', array_fill(0, count($counters), '(?, ?, ?, ?, 1)'));

        foreach ($counters as $key => $hash) {
            array_push($bindings, $tenant, $scope, $hash, $key);
        }

        self::connection()->statement(<<<SQL
            INSERT INTO sequences (tenant, scope, hash, key, value)
            VALUES {$values}
            ON CONFLICT (tenant, scope, hash, key)
            DO UPDATE SET value = sequences.value + 1
            SQL, $bindings);
    }

    public static function load(string $scope, DateTimeInterface $date, ?string $tenant, array $bucket = []): array
    {
        $rows = self::connection()
            ->table('sequences')
            ->where([
                'tenant' => $tenant ?? self::DEFAULT_TENANT,
                'scope' => $scope,
            ])
            ->whereIn('hash', array_values(self::counters($date, $bucket)))
            ->pluck('value', 'key')
            ->all();

        return array_map(
            fn (int $value) => $value + 1,
            array_replace(array_fill_keys(array_keys(self::COUNTERS), 0), $rows),
        );
    }

    private static function watch(string $scope): void
    {
        if (array_key_exists($scope, self::$watched) || !is_subclass_of($scope, Model::class)) {
            return;
        }

        self::$watched[$scope] = true;

        $scope::created(function (Model $record) {
            foreach (self::$registered[$record::class] ?? [] as $scheme) {
                self::store($record, now(), $scheme['tenant'], $scheme['bucket']);
            }
        });
    }

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

    private static function connection(): Connection
    {
        return DB::connection(config('phpinnacle-sequentia.connection'));
    }
}

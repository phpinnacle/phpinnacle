<?php

namespace PHPinnacle\Sequentia;

use DateTimeInterface;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use WeakMap;

final class SequenceWatcher
{
    public const string DEFAULT_TENANT = '00000000-0000-0000-0000-000000000000';

    /** @var WeakMap<Dispatcher, self>|null */
    private static ?WeakMap $watchers = null;

    /** @var array<string, array<string, array{tenant: string|null, bucket: list<string>}>> */
    private array $registered = [];

    public static function rebuild(): void
    {
        $watcher = self::watcher();
        $store = CounterStore::configured();
        $counts = [];
        $now = now();

        foreach (array_keys($watcher->registered) as $scope) {
            if (!is_subclass_of($scope, Model::class)) {
                continue;
            }

            foreach ($scope::query()->lazy() as $record) {
                /** @var DateTimeInterface $date */
                $date = $record->getCreatedAtColumn() !== null
                    ? $record->getAttributeValue($record->getCreatedAtColumn())
                    : $now;

                foreach ($watcher->rows($store, $record, $date) as $identity => $row) {
                    $row['value'] += $counts[$identity]['value'] ?? 0;
                    $counts[$identity] = $row;
                }
            }
        }

        $store->replace($counts);
    }

    /**
     * @param list<string> $scheme
     */
    public static function register(string $scope, ?string $tenant = null, array $scheme = []): void
    {
        $watcher = self::watcher();
        $watch = !array_key_exists($scope, $watcher->registered);
        $key = sha1(serialize($scheme));

        $watcher->registered[$scope][$key] = [
            'tenant' => $tenant,
            'bucket' => $scheme,
        ];

        if ($watch && is_subclass_of($scope, Model::class)) {
            $scope::created(function (Model $record) use ($watcher) {
                $store = CounterStore::configured();
                $store->increment($watcher->rows($store, $record, now()));
            });
        }
    }

    /**
     * @param list<string> $scheme
     */
    public static function store(Model $record, DateTimeInterface $date, ?string $tenant, array $scheme = []): void
    {
        $store = CounterStore::configured();
        $store->increment(self::schemeRows($store, $record, $date, $tenant, $scheme));
    }

    /**
     * @param array<string, mixed> $bucket
     * @return array<string, int>
     */
    public static function load(string $scope, DateTimeInterface $date, ?string $tenant, array $bucket = []): array
    {
        return CounterStore::configured()->load($scope, $date, $tenant, $bucket);
    }

    /**
     * @return array<string, array{tenant: string, scope: string, hash: string, key: string, value: int}>
     */
    private function rows(CounterStore $store, Model $record, DateTimeInterface $date): array
    {
        $rows = [];

        foreach ($this->registered[$record::class] as $scheme) {
            $rows = array_replace($rows, self::schemeRows(
                $store,
                $record,
                $date,
                $scheme['tenant'],
                $scheme['bucket'],
            ));
        }

        return $rows;
    }

    /**
     * @param list<string> $scheme
     * @return array<string, array{tenant: string, scope: string, hash: string, key: string, value: int}>
     */
    private static function schemeRows(
        CounterStore $store,
        Model $record,
        DateTimeInterface $date,
        ?string $tenant,
        array $scheme,
    ): array {
        /** @var string $tenantId */
        $tenantId = $tenant !== null ? $record->getAttributeValue($tenant) : self::DEFAULT_TENANT;
        $bucket = array_combine($scheme, array_map($record->getAttributeValue(...), $scheme));

        return $store->rows($record::class, $date, $tenantId, $bucket);
    }

    private static function watcher(): self
    {
        self::$watchers ??= new WeakMap;

        return self::$watchers[Model::getEventDispatcher()] ??= new self;
    }
}

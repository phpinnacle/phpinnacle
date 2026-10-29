<?php

use Illuminate\Database\Eloquent\Model;
use PHPinnacle\Ferry\Data\ChangeMessage;
use PHPinnacle\Ferry\Data\TrackedTable;
use PHPinnacle\Ferry\Enums\ChangeOperation;
use PHPinnacle\Ferry\Events\RecordCreated;
use PHPinnacle\Ferry\Events\RecordDeleted;
use PHPinnacle\Ferry\Events\RecordUpdated;
use PHPinnacle\Ferry\Origins\Kafka\SourceFactory;
use PHPinnacle\Ferry\Services\TrackedTableRegistry;
use PHPinnacle\Ferry\Tests\TestCase;

require_once __DIR__ . '/../TestCase.php';

uses(TestCase::class);

class TrackedProductModel extends Model
{
    protected $table = 'products';

    protected $connection = 'pgsql';
}

class TrackedBrandModel extends Model
{
    protected $table = 'brands';

    protected $connection = 'pgsql';
}

class TrackedCustomerModel extends Model
{
    protected $table = 'persons';

    protected $connection = 'sales';
}

/**
 * @param array<string, mixed>|null $before
 * @param array<string, mixed>|null $after
 */
function cdc_message(
    int $offset,
    string $op,
    ?array $before = null,
    ?array $after = null,
    string $table = 'products',
): ChangeMessage {
    return new ChangeMessage(
        topic: $table === 'persons' ? 'ferry.cdc.sales.public.persons' : 'ferry.cdc.pgsql.public.' . $table,
        partition: 0,
        offset: $offset,
        payload: json_encode([
            'payload' => [
                'op' => $op,
                'before' => $before,
                'after' => $after,
                'source' => [
                    'db' => 'prozoo',
                    'schema' => 'public',
                    'table' => $table,
                    'ts_ms' => 1_700_000_000_000 + $offset,
                    'lsn' => 1000 + $offset,
                    'txId' => 500 + $offset,
                ],
            ],
        ], JSON_THROW_ON_ERROR),
    );
}

function cdc_registry(): TrackedTableRegistry
{
    $registry = new TrackedTableRegistry(new SourceFactory);
    $registry->register(
        TrackedTable::forModel(TrackedProductModel::class),
        TrackedTable::forModel(TrackedCustomerModel::class, ['type' => 'customer']),
    );

    return $registry;
}

it('builds the application source connector config for tracked tables', function () {
    config([
        'database.connections.pgsql.host' => 'app-db.internal',
        'database.connections.pgsql.port' => 5432,
        'database.connections.pgsql.database' => 'prozoo',
        'database.connections.pgsql.username' => 'prozoo_user',
        'database.connections.pgsql.password' => 'prozoo_secret',
    ]);

    $sources = new SourceFactory;
    $product = TrackedTable::forModel(TrackedProductModel::class);
    $config = $sources->application('pgsql', [$product, TrackedTable::forModel(TrackedBrandModel::class)]);

    expect($sources->applicationName('pgsql'))
        ->toBe('ferry-cdc-pgsql')
        ->and($sources->applicationTopic($product))
        ->toBe('ferry.cdc.pgsql.public.products')
        ->and($config)
        ->toMatchArray([
            'connector.class' => 'io.debezium.connector.postgresql.PostgresConnector',
            'database.hostname' => 'app-db.internal',
            'database.port' => '5432',
            'database.dbname' => 'prozoo',
            'database.user' => 'prozoo_user',
            'database.password' => 'prozoo_secret',
            'slot.name' => 'ferry_cdc_slot_pgsql',
            'publication.name' => 'ferry_cdc_pub_pgsql',
            'topic.prefix' => 'ferry.cdc.pgsql',
            'table.include.list' => 'public.products,public.brands',
            'snapshot.mode' => 'no_data',
            'decimal.handling.mode' => 'string',
            'tombstones.on.delete' => 'false',
            'heartbeat.interval.ms' => '10000',
        ]);
});

it('resolves the tracked connection from the model and the default connection', function () {
    $model = new class extends Model {
        protected $table = 'items';
    };

    expect(TrackedTable::forModel(TrackedCustomerModel::class)->connection)
        ->toBe('sales')
        ->and(TrackedTable::forModel($model::class)->connection)
        ->toBe(config('database.default'));
});

it('parses create, update and delete changes into events with their own state', function () {
    $registry = cdc_registry();

    $created = $registry->change(cdc_message(offset: 10, op: 'c', after: ['id' => 'p1', 'name' => 'Kibble']));
    $updated = $registry->change(cdc_message(
        offset: 11,
        op: 'u',
        before: ['id' => 'p1', 'name' => 'Kibble'],
        after: ['id' => 'p1', 'name' => 'Premium Kibble'],
    ));
    $deleted = $registry->change(cdc_message(offset: 12, op: 'd', before: ['id' => 'p1', 'name' => 'Premium Kibble']));

    expect($created)
        ->toBeInstanceOf(RecordCreated::class)
        ->and($created?->operation())
        ->toBe(ChangeOperation::Create)
        ->and($created?->eventId)
        ->toBe('ferry.cdc.pgsql.public.products:0:10')
        ->and($created?->model)
        ->toBe(TrackedProductModel::class)
        ->and($created?->key)
        ->toBe('p1')
        ->and($created?->metadata)
        ->toBe([
            'topic' => 'ferry.cdc.pgsql.public.products',
            'partition' => 0,
            'offset' => 10,
            'timestamp' => 1_700_000_000_010,
            'database' => 'prozoo',
            'schema' => 'public',
            'table' => 'products',
            'lsn' => 1010,
            'txId' => 510,
        ])
        ->and($updated)
        ->toBeInstanceOf(RecordUpdated::class)
        ->and($updated?->before)
        ->toBe(['id' => 'p1', 'name' => 'Kibble'])
        ->and($updated?->after)
        ->toBe(['id' => 'p1', 'name' => 'Premium Kibble'])
        ->and($deleted)
        ->toBeInstanceOf(RecordDeleted::class)
        ->and($deleted?->key)
        ->toBe('p1')
        ->and($deleted?->attributes())
        ->toBe(['id' => 'p1', 'name' => 'Premium Kibble']);
});

it('ignores tombstones, snapshot reads and updates without changes', function (ChangeMessage $message) {
    expect(cdc_registry()->change($message))->toBeNull();
})->with([
    'tombstone' => [new ChangeMessage('ferry.cdc.pgsql.public.products', 0, 1, null)],
    'snapshot read' => [cdc_message(offset: 2, op: 'r', after: ['id' => 'p1', 'name' => 'Existing'])],
    'unchanged update' => [cdc_message(
        offset: 3,
        op: 'u',
        before: ['id' => 'p1', 'name' => 'Same'],
        after: ['id' => 'p1', 'name' => 'Same'],
    )],
]);

it('restores unchanged TOAST values from the previous state', function () {
    $event = cdc_registry()->change(cdc_message(
        offset: 5,
        op: 'u',
        before: ['id' => 'p1', 'name' => 'Old', 'description' => 'Full text'],
        after: ['id' => 'p1', 'name' => 'New', 'description' => '__debezium_unavailable_value'],
    ));

    expect($event?->after)->toBe(['id' => 'p1', 'name' => 'New', 'description' => 'Full text']);
});

it('skips unregistered tables and resolves single table inheritance discriminators', function () {
    $registry = cdc_registry();

    expect($registry->change(cdc_message(offset: 1, op: 'c', after: ['id' => 'c1'], table: 'categories')))
        ->toBeNull()
        ->and($registry->change(cdc_message(
            offset: 2,
            op: 'c',
            after: ['id' => 'e1', 'type' => 'employee'],
            table: 'persons',
        )))
        ->toBeNull()
        ->and($registry->change(cdc_message(
            offset: 3,
            op: 'c',
            after: ['id' => 'c1', 'type' => 'customer'],
            table: 'persons',
        ))?->model)
        ->toBe(TrackedCustomerModel::class);
});

<?php

use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use PHPinnacle\Ferry\Contracts\ChangeConsumer;
use PHPinnacle\Ferry\Data\ChangeMessage;
use PHPinnacle\Ferry\Data\TrackedTable;
use PHPinnacle\Ferry\Enums\ConnectorStatus;
use PHPinnacle\Ferry\Enums\ReplicaStatus;
use PHPinnacle\Ferry\Enums\StructureStatus;
use PHPinnacle\Ferry\Events\RecordChanged;
use PHPinnacle\Ferry\Events\RecordCreated;
use PHPinnacle\Ferry\Events\RecordDeleted;
use PHPinnacle\Ferry\Events\RecordUpdated;
use PHPinnacle\Ferry\Models\Connector;
use PHPinnacle\Ferry\Origins\Kafka\KafkaConnectFactory;
use PHPinnacle\Ferry\Origins\Kafka\SinkConfigFactory;
use PHPinnacle\Ferry\Origins\Kafka\SourceFactory;
use PHPinnacle\Ferry\Origins\Kafka\TransformFactory;
use PHPinnacle\Ferry\Origins\KafkaOrigin;
use PHPinnacle\Ferry\Services\ConnectorManager;
use PHPinnacle\Ferry\Services\DestinationFactory;
use PHPinnacle\Ferry\Services\OriginFactory;
use PHPinnacle\Ferry\Services\TrackedTableRegistry;
use PHPinnacle\Ferry\Tests\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

require_once __DIR__ . '/../TestCase.php';

uses(TestCase::class);

class CdcProductFixture extends Model
{
    protected $table = 'products';

    protected $connection = 'pgsql';
}

final class InMemoryChangeConsumer implements ChangeConsumer
{
    /** @var list<ChangeMessage> */
    public array $committed = [];

    public bool $closed = false;

    /** @var list<ChangeMessage> */
    private array $queue;

    public function __construct(ChangeMessage ...$messages)
    {
        $this->queue = array_values($messages);
    }

    public function subscribe(array $topics): void {}

    public function consume(int $timeout): ?ChangeMessage
    {
        return array_shift($this->queue);
    }

    public function commit(ChangeMessage $message): void
    {
        $this->committed[] = $message;
    }

    public function lag(array $topics): array
    {
        return array_fill_keys($topics, count($this->queue));
    }

    public function close(): void
    {
        $this->closed = true;
    }
}

final class CdcRecordingHttpClient implements ClientInterface
{
    /** @var list<RequestInterface> */
    public array $requests = [];

    /** @var list<ResponseInterface> */
    private array $responses;

    public function __construct(ResponseInterface ...$responses)
    {
        $this->responses = array_values($responses);
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->requests[] = $request;

        return array_shift($this->responses) ?? new Response(200, [], '{}');
    }

    /** @return list<array{string, string}> */
    public function routes(): array
    {
        return array_map(static fn (RequestInterface $request) => [
            $request->getMethod(),
            $request->getUri()->getPath(),
        ], $this->requests);
    }
}

/**
 * @param array<string, mixed>|null $before
 * @param array<string, mixed>|null $after
 */
function product_change(int $offset, string $op, ?array $before = null, ?array $after = null): ChangeMessage
{
    return new ChangeMessage('ferry.cdc.pgsql.public.products', 0, $offset, json_encode([
        'payload' => [
            'op' => $op,
            'before' => $before,
            'after' => $after,
            'source' => [
                'db' => 'prozoo',
                'schema' => 'public',
                'table' => 'products',
                'ts_ms' => 1_700_000_000_000,
                'lsn' => $offset,
                'txId' => $offset,
            ],
        ],
    ], JSON_THROW_ON_ERROR));
}

function cdc_connector_manager(CdcRecordingHttpClient $http): ConnectorManager
{
    $factory = new HttpFactory;
    app()->instance(ClientInterface::class, $http);
    app()->instance(RequestFactoryInterface::class, $factory);
    app()->instance(StreamFactoryInterface::class, $factory);

    $origin = new KafkaOrigin(
        'kafka',
        config('phpinnacle-ferry.origins.kafka'),
        new KafkaConnectFactory(app()),
        new SourceFactory,
        new DestinationFactory,
        new SinkConfigFactory(app(TransformFactory::class)),
    );
    $origins = Mockery::mock(OriginFactory::class);
    $origins->shouldReceive('create')->with('kafka')->andReturn($origin);
    app()->instance(OriginFactory::class, $origins);

    return app(ConnectorManager::class);
}

beforeEach(function () {
    app()->forgetInstance(TrackedTableRegistry::class);
    app(TrackedTableRegistry::class)->register(TrackedTable::forModel(CdcProductFixture::class));
});

it('dispatches rapid sequential changes of one record in order with their own state', function () {
    $consumer = new InMemoryChangeConsumer(
        product_change(offset: 1, op: 'c', after: ['id' => 'p1', 'name' => 'V1']),
        product_change(
            offset: 2,
            op: 'u',
            before: ['id' => 'p1', 'name' => 'V1'],
            after: ['id' => 'p1', 'name' => 'V2'],
        ),
        product_change(
            offset: 3,
            op: 'u',
            before: ['id' => 'p1', 'name' => 'V2'],
            after: ['id' => 'p1', 'name' => 'V3'],
        ),
        product_change(offset: 4, op: 'd', before: ['id' => 'p1', 'name' => 'V3']),
    );
    $this->app->instance(ChangeConsumer::class, $consumer);

    /** @var list<RecordChanged> $received */
    $received = [];
    Event::listen([
        RecordCreated::class,
        RecordUpdated::class,
        RecordDeleted::class,
    ], function (RecordChanged $event) use (&$received, $consumer) {
        expect($consumer->committed)->toHaveCount(count($received));
        $received[] = $event;
    });

    Artisan::call('ferry:cdc-consume', ['--max-messages' => 4]);

    expect(array_map(fn (RecordChanged $event) => [
        $event::class,
        $event->before['name'] ?? null,
        $event->after['name'] ?? null,
    ], $received))
        ->toBe([
            [RecordCreated::class, null, 'V1'],
            [RecordUpdated::class, 'V1', 'V2'],
            [RecordUpdated::class, 'V2', 'V3'],
            [RecordDeleted::class, 'V3', null],
        ])
        ->and(array_map(fn (ChangeMessage $message) => $message->offset, $consumer->committed))
        ->toBe([1, 2, 3, 4])
        ->and($consumer->closed)
        ->toBeTrue();
});

it('keeps the offset uncommitted when a listener fails and redelivers the change', function () {
    $message = product_change(offset: 7, op: 'c', after: ['id' => 'p1', 'name' => 'Retryable']);
    $failing = new InMemoryChangeConsumer($message);
    $this->app->instance(ChangeConsumer::class, $failing);

    $shouldFail = true;
    $processed = [];
    Event::listen(RecordCreated::class, function (RecordCreated $event) use (&$shouldFail, &$processed) {
        if ($shouldFail) {
            throw new RuntimeException('Listener failure');
        }

        $processed[] = $event->eventId;
    });

    expect(fn () => Artisan::call('ferry:cdc-consume', ['--once' => true]))
        ->toThrow(RuntimeException::class, 'Listener failure')
        ->and($failing->committed)
        ->toBe([])
        ->and($failing->closed)
        ->toBeTrue()
        ->and(Context::get('ferry_cdc_message'))
        ->toBe('ferry.cdc.pgsql.public.products:0:7');

    $shouldFail = false;
    $retry = new InMemoryChangeConsumer($message);
    $this->app->instance(ChangeConsumer::class, $retry);

    Artisan::call('ferry:cdc-consume', ['--once' => true]);

    expect($processed)
        ->toBe(['ferry.cdc.pgsql.public.products:0:7'])
        ->and($retry->committed)
        ->toBe([$message])
        ->and($retry->closed)
        ->toBeTrue();
});

it('refuses to commit a malformed change message', function () {
    $consumer = new InMemoryChangeConsumer(new ChangeMessage('ferry.cdc.pgsql.public.products', 0, 13, '{invalid'));
    $this->app->instance(ChangeConsumer::class, $consumer);

    expect(fn () => Artisan::call('ferry:cdc-consume', ['--once' => true]))
        ->toThrow(JsonException::class)
        ->and($consumer->committed)
        ->toBe([])
        ->and($consumer->closed)
        ->toBeTrue()
        ->and(Context::get('ferry_cdc_message'))
        ->toBe('ferry.cdc.pgsql.public.products:0:13');
});

it('manages the application source connector without touching 1C connectors', function () {
    Queue::fake();
    $upstream = TestCase::makeUpstream([], ['status' => StructureStatus::Ready, 'generation' => 1]);
    TestCase::makeItem($upstream);
    $replica = TestCase::makeReplica(['upstream_id' => $upstream->id], ['status' => ReplicaStatus::Active]);
    $source = TestCase::makeConnector($upstream, [
        'name' => 'ferry-source-test-upstream',
        'config' => ['a' => 'b'],
    ]);
    $sink = TestCase::makeConnector($replica, ['name' => 'ferry-sink-test-replica', 'config' => ['c' => 'd']]);

    $http = new CdcRecordingHttpClient(
        new Response(200, [], json_encode([
            'name' => 'ferry-cdc-pgsql',
            'config' => [],
            'tasks' => [],
        ], JSON_THROW_ON_ERROR)),
        new Response(202),
        new Response(200, [], json_encode([
            'name' => 'ferry-cdc-pgsql',
            'connector' => ['state' => 'RUNNING', 'worker_id' => 'w:8083'],
            'tasks' => [],
        ], JSON_THROW_ON_ERROR)),
    );

    $result = cdc_connector_manager($http)->syncApplicationSource('pgsql', [
        TrackedTable::forModel(CdcProductFixture::class),
    ]);
    $sentConfig = json_decode((string) $http->requests[0]->getBody(), true, flags: JSON_THROW_ON_ERROR);

    expect($result['status'])
        ->toBe(ConnectorStatus::Running)
        ->and($sentConfig['table.include.list'] ?? null)
        ->toBe('public.products')
        ->and($http->routes())
        ->toBe([
            ['PUT', '/connectors/ferry-cdc-pgsql/config'],
            ['PUT', '/connectors/ferry-cdc-pgsql/resume'],
            ['GET', '/connectors/ferry-cdc-pgsql/status'],
        ])
        ->and($upstream->fresh()?->connector_id)
        ->toBe($source->id)
        ->and($replica->fresh()?->connector_id)
        ->toBe($sink->id)
        ->and($source->fresh()?->config)
        ->toBe(['a' => 'b'])
        ->and($sink->fresh()?->config)
        ->toBe(['c' => 'd']);
});

it('checks the application source status without creating a connector record', function () {
    $http = new CdcRecordingHttpClient(new Response(
        404,
        [],
        '{"error_code":404,"message":"Connector not found"}',
    ));

    expect(cdc_connector_manager($http)->checkApplicationSource('pgsql'))
        ->toBe(['status' => ConnectorStatus::Unknown, 'error' => null])
        ->and($http->routes())
        ->toBe([['GET', '/connectors/ferry-cdc-pgsql/status']])
        ->and(Connector::query()->where('name', 'ferry-cdc-pgsql')->exists())
        ->toBeFalse();
});

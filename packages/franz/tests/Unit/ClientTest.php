<?php

namespace PHPinnacle\Franz\Tests\Unit;

use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use LogicException;
use PHPinnacle\Franz\Client;
use PHPinnacle\Franz\Enum\InitialState;
use PHPinnacle\Franz\Enum\LoggerScope;
use PHPinnacle\Franz\Enum\LogLevel;
use PHPinnacle\Franz\Exception\ApiException;
use PHPinnacle\Franz\Exception\UnexpectedResponseException;
use PHPinnacle\Franz\Request\AlterConnectorOffsetsRequest;
use PHPinnacle\Franz\Request\ConnectorConfigRequest;
use PHPinnacle\Franz\Request\CreateConnectorRequest;
use PHPinnacle\Franz\Request\PatchConnectorConfigRequest;
use PHPinnacle\Franz\Value\ConnectorOffset;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use UnexpectedValueException;

final class ClientTest extends TestCase
{
    public function test_it_creates_reads_replaces_patches_and_deletes_a_connector(): void
    {
        $name = 'school/source #?%';
        $config = ['connector.class' => 'FileStreamSource', 'tasks.max' => '1', 'topic' => 'lessons'];
        $info = [
            'name' => $name,
            'config' => $config,
            'tasks' => [['connector' => $name, 'task' => 0]],
            'type' => 'source',
        ];
        $http = new RecordingHttpClient(
            $this->response($info, 201),
            $this->response($info),
            $this->response($config),
            $this->response($info),
            $this->response($info),
            new Response(204),
        );
        $client = $this->client($http);
        $created = $client->createConnector(new CreateConnectorRequest($name, $config, InitialState::Stopped));
        $connector = $client->connector($name);

        self::assertSame($name, $created->name);
        self::assertSame(0, $created->tasks[0]->task);
        self::assertSame($name, $created->tasks[0]->connector);
        self::assertSame('source', $created->type);
        self::assertSame($info, $created->raw);
        self::assertSame($config, $connector->details()->config);
        self::assertSame($config, $connector->config());
        $connector->updateConfig(new ConnectorConfigRequest($config));
        $connector->patchConfig(new PatchConnectorConfigRequest(['tasks.max' => '2', 'file' => null]));
        $connector->delete();

        self::assertSame(
            ['name' => $name, 'config' => $config, 'initial_state' => 'STOPPED'],
            $this->body($http->requests[0]),
        );
        self::assertSame($config, $this->body($http->requests[3]));
        self::assertSame(['tasks.max' => '2', 'file' => null], $this->body($http->requests[4]));
        self::assertSame('application/json', $http->requests[0]->getHeaderLine('Content-Type'));
        self::assertSame('application/json', $http->requests[0]->getHeaderLine('Accept'));
        self::assertSame('', (string) $http->requests[5]->getBody());
        $path = '/connectors/school%2Fsource%20%23%3F%25';
        self::assertSame(
            [
                ['POST', '/connectors'],
                ['GET', $path],
                ['GET', $path . '/config'],
                ['PUT', $path . '/config'],
                ['PATCH', $path . '/config'],
                ['DELETE', $path],
            ],
            $this->routes($http),
        );
    }

    public function test_it_preserves_empty_json_configuration_objects_and_omits_unspecified_initial_state(): void
    {
        $info = ['name' => 'source', 'config' => [], 'tasks' => []];
        $http = new RecordingHttpClient($this->response($info), $this->response($info), $this->response($info));
        $client = $this->client($http);
        $client->createConnector(new CreateConnectorRequest('source', []));
        $client->connector('source')->updateConfig(new ConnectorConfigRequest([]));
        $client->connector('source')->patchConfig(new PatchConnectorConfigRequest([]));

        self::assertSame('{"name":"source","config":{}}', (string) $http->requests[0]->getBody());
        self::assertSame('{}', (string) $http->requests[1]->getBody());
        self::assertSame('{}', (string) $http->requests[2]->getBody());
    }

    public function test_it_reads_worker_information_and_expands_connectors_in_one_request(): void
    {
        $info = ['name' => 'source', 'config' => ['tasks.max' => '1'], 'tasks' => [], 'type' => 'source'];
        $http = new RecordingHttpClient(
            $this->response(['version' => '4.2.0', 'commit' => 'abc123', 'kafka_cluster_id' => 'cluster-1']),
            $this->response(['source', 'sink']),
            $this->response(['source' => ['info' => $info, 'status' => $this->statusPayload()]]),
            $this->response([]),
        );
        $client = $this->client($http);
        $worker = $client->info();
        self::assertSame('4.2.0', $worker->version);
        self::assertSame('abc123', $worker->commit);
        self::assertSame('cluster-1', $worker->kafkaClusterId);
        self::assertSame(['source', 'sink'], $client->connectors());
        $expanded = $client->expandedConnectors();
        self::assertSame('source', $expanded['source']->info->name);
        self::assertSame('RUNNING', $expanded['source']->status->connector->state);
        self::assertSame('expand=info&expand=status', $http->requests[2]->getUri()->getQuery());
        self::assertSame([], $client->connectors());
        self::assertSame(
            [
                ['GET', '/'],
                ['GET', '/connectors'],
                ['GET', '/connectors'],
                ['GET', '/connectors'],
            ],
            $this->routes($http),
        );
    }

    public function test_it_pauses_resumes_stops_and_restarts_without_polling_or_hidden_requests(): void
    {
        $http = new RecordingHttpClient(
            new Response(202),
            new Response(202),
            new Response(204),
            new Response(204),
            $this->response($this->statusPayload('RESTARTING'), 202),
            new Response(200),
            $this->response($this->statusPayload()),
        );
        $connector = $this->client($http)->connector('source');
        $connector->pause();
        $connector->resume();
        $connector->stop();
        self::assertNull($connector->restart());
        $restart = $connector->restart(includeTasks: true, onlyFailed: true);
        self::assertSame('RESTARTING', $restart?->connector->state);
        self::assertNull($connector->restart(onlyFailed: true));
        $status = $connector->status();
        self::assertSame('source', $status->name);
        self::assertSame('source', $status->type);
        self::assertSame('worker:8083', $status->connector->workerId);
        self::assertNull($status->connector->trace);
        self::assertSame('FAILED', $status->tasks[0]->state);
        self::assertSame('Failure details', $status->tasks[0]->trace);
        self::assertSame('includeTasks=false&onlyFailed=false', $http->requests[3]->getUri()->getQuery());
        self::assertSame('includeTasks=true&onlyFailed=true', $http->requests[4]->getUri()->getQuery());
        self::assertSame('includeTasks=false&onlyFailed=true', $http->requests[5]->getUri()->getQuery());
        self::assertSame(
            [
                ['PUT',  '/connectors/source/pause'],
                ['PUT',  '/connectors/source/resume'],
                ['PUT',  '/connectors/source/stop'],
                ['POST', '/connectors/source/restart'],
                ['POST', '/connectors/source/restart'],
                ['POST', '/connectors/source/restart'],
                ['GET',  '/connectors/source/status'],
            ],
            $this->routes($http),
        );
        foreach ($http->requests as $request) {
            self::assertSame('', (string) $request->getBody());
        }
    }

    public function test_it_reads_worker_health(): void
    {
        $http = new RecordingHttpClient($this->response(['status' => 'healthy', 'message' => 'Ready']));
        $health = $this->client($http)->health();
        self::assertSame('healthy', $health->status);
        self::assertSame('Ready', $health->message);
        self::assertSame([['GET', '/health']], $this->routes($http));
    }

    public function test_it_selects_a_plugin_configuration_version(): void
    {
        $http = new RecordingHttpClient($this->response([]));
        self::assertSame([], $this->client($http)->plugin('FileStreamSource')->config(version: '4.2.0'));
        self::assertSame('version=4.2.0', $http->requests[0]->getUri()->getQuery());
    }

    public function test_it_rejects_a_missing_offset_without_interpreting_it_as_a_partition_reset(): void
    {
        $http = new RecordingHttpClient($this->response([
            'offsets' => [['partition' => ['filename' => 'lessons.txt']]],
        ]));
        $this->expectException(UnexpectedValueException::class);
        $this->client($http)->connector('source')->offsets();
    }

    public function test_it_lists_tasks_and_reads_and_restarts_task_zero(): void
    {
        $http = new RecordingHttpClient(
            $this->response([[
                'id' => ['connector' => 'source', 'task' => 0],
                'config' => ['task.class' => 'SourceTask'],
            ]]),
            $this->response([
                'id' => 0,
                'state' => 'FAILED',
                'worker_id' => 'worker:8083',
                'trace' => 'Failure details',
            ]),
            new Response(204),
        );
        $connector = $this->client($http)->connector('source');
        $tasks = $connector->tasks();
        self::assertSame(0, $tasks[0]->id->task);
        self::assertSame(['task.class' => 'SourceTask'], $tasks[0]->config);
        $status = $connector->task(0)->status();
        self::assertSame(0, $status->id);
        self::assertSame('worker:8083', $status->workerId);
        self::assertSame('Failure details', $status->trace);
        $connector->task(0)->restart();
        self::assertSame(
            [
                ['GET',  '/connectors/source/tasks'],
                ['GET',  '/connectors/source/tasks/0/status'],
                ['POST', '/connectors/source/tasks/0/restart'],
            ],
            $this->routes($http),
        );
    }

    public function test_it_reads_and_resets_topic_tracking_for_the_named_connector(): void
    {
        $http = new RecordingHttpClient($this->response([
            'source' => ['topics' => ['lessons', 'students']],
        ]), new Response(202));
        $connector = $this->client($http)->connector('source');
        self::assertSame(['lessons', 'students'], $connector->topics());
        $connector->resetTopics();
        self::assertSame(
            [
                ['GET', '/connectors/source/topics'],
                ['PUT', '/connectors/source/topics/reset'],
            ],
            $this->routes($http),
        );
    }

    public function test_it_reads_alters_and_resets_offsets_preserving_partition_zero_and_null_resets(): void
    {
        $http = new RecordingHttpClient(
            $this->response([
                'offsets' => [
                    [
                        'partition' => ['kafka_topic' => 'lessons', 'kafka_partition' => 0],
                        'offset' => ['kafka_offset' => 0],
                    ],
                    ['partition' => ['filename' => 'lessons.txt'], 'offset' => ['position' => 30]],
                    ['partition' => ['filename' => 'deleted.txt'], 'offset' => null],
                ],
            ]),
            $this->response(['message' => 'Offsets altered successfully']),
            $this->response(['message' => 'Offsets reset successfully']),
        );
        $connector = $this->client($http)->connector('source');
        $offsets = $connector->offsets();
        self::assertSame(['kafka_offset' => 0], $offsets[0]->offset);
        self::assertSame(['filename' => 'lessons.txt'], $offsets[1]->partition);
        self::assertNull($offsets[2]->offset);
        $result = $connector->alterOffsets(new AlterConnectorOffsetsRequest([
            $offsets[0],
            new ConnectorOffset(['kafka_topic' => 'lessons', 'kafka_partition' => 1], null),
            new ConnectorOffset([], []),
        ]));
        self::assertSame('Offsets altered successfully', $result->message);
        self::assertSame(
            '{"offsets":[{"partition":{"kafka_topic":"lessons","kafka_partition":0},"offset":{"kafka_offset":0}},'
            . '{"partition":{"kafka_topic":"lessons","kafka_partition":1},"offset":null},{"partition":{},"offset":{}}]}',
            (string) $http->requests[1]->getBody(),
        );
        self::assertSame('Offsets reset successfully', $connector->resetOffsets()->message);
        self::assertSame(
            [
                ['GET',    '/connectors/source/offsets'],
                ['PATCH',  '/connectors/source/offsets'],
                ['DELETE', '/connectors/source/offsets'],
            ],
            $this->routes($http),
        );
    }

    public function test_it_lists_plugins_with_missing_or_null_versions(): void
    {
        $payload = [
            ['class' => 'FileStreamSource', 'type' => 'source', 'version' => '4.2.0'],
            ['class' => 'io.confluent.connect.transforms.Filter$Value', 'type' => 'transformation'],
            ['class' => 'TopicNameMatches', 'type' => 'predicate', 'version' => null],
        ];
        $http = new RecordingHttpClient($this->response($payload));
        $plugins = $this->client($http)->plugins(connectorsOnly: false);

        self::assertCount(3, $plugins);
        self::assertSame('4.2.0', $plugins[0]->version);
        self::assertNull($plugins[1]->version);
        self::assertNull($plugins[2]->version);
        self::assertSame($payload, array_column($plugins, 'raw'));
    }

    public function test_it_rejects_non_string_plugin_versions(): void
    {
        $http = new RecordingHttpClient($this->response([
            ['class' => 'FileStreamSource', 'type' => 'source', 'version' => 42],
        ]));

        $this->expectException(UnexpectedValueException::class);
        $this->client($http)->plugins(connectorsOnly: false);
    }

    public function test_it_lists_plugins_and_returns_configuration_definitions_and_validation_errors(): void
    {
        $definition = [
            'name' => 'tasks.max',
            'type' => 'INT',
            'required' => false,
            'default_value' => '1',
            'importance' => 'HIGH',
            'documentation' => 'Maximum tasks',
            'group' => 'Common',
            'dependents' => [],
        ];
        $validation = [
            'name' => 'FileStreamSource',
            'error_count' => 1,
            'groups' => ['Common'],
            'configs' => [[
                'definition' => $definition,
                'value' => [
                    'name' => 'tasks.max',
                    'value' => '0',
                    'recommended_values' => [],
                    'errors' => ['Must be positive'],
                    'visible' => true,
                ],
            ]],
        ];
        $http = new RecordingHttpClient(
            $this->response([['class' => 'FileStreamSource', 'type' => 'source', 'version' => '4.2.0']]),
            $this->response([['class' => 'HoistField$Value', 'type' => 'transformation', 'version' => '4.2.0']]),
            $this->response([$definition]),
            $this->response($validation),
            $this->response(['name' => 'FileStreamSource', 'error_count' => 0, 'groups' => [], 'configs' => []]),
        );
        $client = $this->client($http);
        $pluginInfo = $client->plugins()[0];
        self::assertSame('FileStreamSource', $pluginInfo->class);
        self::assertSame('4.2.0', $pluginInfo->version);
        self::assertSame('transformation', $client->plugins(connectorsOnly: false)[0]->type);
        $plugin = $client->plugin('FileStreamSource');
        $definitions = $plugin->config();
        self::assertSame('tasks.max', $definitions[0]->name);
        self::assertFalse($definitions[0]->required);
        self::assertSame('Common', $definitions[0]->raw['group']);
        $result = $plugin->validateConfig(new ConnectorConfigRequest([
            'connector.class' => 'FileStreamSource',
            'tasks.max' => '0',
        ]));
        self::assertFalse($result->isValid());
        self::assertSame(1, $result->errorCount);
        self::assertSame(['Common'], $result->groups);
        self::assertSame(['Must be positive'], $result->configs[0]->value->errors);
        self::assertSame('0', $result->configs[0]->value->value);
        self::assertTrue($result->configs[0]->value->visible);
        self::assertSame('INT', $result->configs[0]->definition?->type);
        self::assertTrue($plugin->validateConfig(new ConnectorConfigRequest([]))->isValid());
        self::assertSame('connectorsOnly=true', $http->requests[0]->getUri()->getQuery());
        self::assertSame('connectorsOnly=false', $http->requests[1]->getUri()->getQuery());
        self::assertSame(
            ['connector.class' => 'FileStreamSource', 'tasks.max' => '0'],
            $this->body($http->requests[3]),
        );
        self::assertSame('{}', (string) $http->requests[4]->getBody());
        self::assertSame(
            [
                ['GET', '/connector-plugins'],
                ['GET', '/connector-plugins'],
                ['GET', '/connector-plugins/FileStreamSource/config'],
                ['PUT', '/connector-plugins/FileStreamSource/config/validate'],
                ['PUT', '/connector-plugins/FileStreamSource/config/validate'],
            ],
            $this->routes($http),
        );
    }

    public function test_it_reads_loggers_and_changes_worker_and_cluster_log_levels(): void
    {
        $http = new RecordingHttpClient(
            $this->response(['root' => ['level' => 'INFO', 'last_modified' => 123]]),
            $this->response(['level' => 'INFO']),
            $this->response(['org.apache.kafka.connect', 'org.apache.kafka.connect.runtime']),
            new Response(204),
        );
        $client = $this->client($http);
        self::assertSame(123, $client->loggers()['root']->lastModified);
        $logger = $client->logger('org.apache.kafka.connect');
        self::assertSame('INFO', $logger->details()->level);
        self::assertSame(
            ['org.apache.kafka.connect', 'org.apache.kafka.connect.runtime'],
            $logger->setLevel(LogLevel::Debug),
        );
        self::assertNull($logger->setLevel(LogLevel::Info, LoggerScope::Cluster));
        self::assertSame(['level' => 'DEBUG'], $this->body($http->requests[2]));
        self::assertSame('scope=worker', $http->requests[2]->getUri()->getQuery());
        self::assertSame('scope=cluster', $http->requests[3]->getUri()->getQuery());
        self::assertSame(
            [
                ['GET', '/admin/loggers'],
                ['GET', '/admin/loggers/org.apache.kafka.connect'],
                ['PUT', '/admin/loggers/org.apache.kafka.connect'],
                ['PUT', '/admin/loggers/org.apache.kafka.connect'],
            ],
            $this->routes($http),
        );
    }

    public function test_it_preserves_base_path_headers_and_raw_json_without_mutating_another_client(): void
    {
        $http = new RecordingHttpClient($this->response(['ready' => true]), new Response(204));
        $factory = new HttpFactory;
        $client = new Client('https://connect.example/proxy/connect/', $http, $factory, $factory, [
            'Authorization' => 'Basic example',
            'X-Request-ID' => 'request-1',
        ]);
        self::assertSame(
            ['ready' => true],
            $client->request(
                'patch',
                '/extension',
                ['enabled' => false, 'name' => 'A B'],
                ['enabled' => false, 'clear' => null],
            ),
        );
        self::assertSame(
            'https://connect.example/proxy/connect/extension?enabled=false&name=A%20B',
            (string) $http->requests[0]->getUri(),
        );
        self::assertSame('Basic example', $http->requests[0]->getHeaderLine('Authorization'));
        self::assertSame('request-1', $http->requests[0]->getHeaderLine('X-Request-ID'));
        self::assertSame(['enabled' => false, 'clear' => null], $this->body($http->requests[0]));
        self::assertNull($this->client($http)->request('DELETE', '/extension'));
        self::assertSame('', $http->requests[1]->getHeaderLine('Authorization'));
    }

    #[DataProvider('errorResponses')]
    public function test_it_preserves_api_errors_without_retrying(
        int $status,
        string $body,
        ?int $errorCode,
        string $message,
    ): void {
        $http = new RecordingHttpClient(new Response($status, [], $body));
        try {
            $this->client($http)->connector('source')->delete();
            self::fail('Expected an API exception.');
        } catch (ApiException $exception) {
            self::assertSame($status, $exception->statusCode);
            self::assertSame($status, $exception->getCode());
            self::assertSame($body, $exception->responseBody);
            self::assertSame($errorCode, $exception->errorCode);
            self::assertSame($message, $exception->getMessage());
            self::assertCount(1, $http->requests);
        }
    }

    public static function errorResponses(): iterable
    {
        yield 'not found' => [404, '{"error_code":404,"message":"Unknown connector"}', 404, 'Unknown connector'];
        yield 'rebalance' => [
            409,
            '{"error_code":409,"message":"Rebalance in progress"}',
            409,
            'Rebalance in progress',
        ];
        yield 'validation' => [
            400,
            '{"error_code":400,"message":"Invalid configuration"}',
            400,
            'Invalid configuration',
        ];
        yield 'unauthorized' => [
            401,
            '{"error_code":401,"message":"Authentication required"}',
            401,
            'Authentication required',
        ];
        yield 'unavailable' => [503, '<html>Unavailable</html>', null, 'Kafka Connect API returned HTTP 503.'];
        yield 'starting worker' => [
            503,
            '{"status":"starting","message":"Worker is starting"}',
            null,
            'Worker is starting',
        ];
        yield 'empty' => [500, '', null, 'Kafka Connect API returned HTTP 500.'];
    }

    #[DataProvider('invalidJsonResponses')]
    public function test_it_reports_invalid_json_responses(string $body): void
    {
        try {
            $this->client(new RecordingHttpClient(new Response(200, [], $body)))->info();
            self::fail('Expected an unexpected response exception.');
        } catch (UnexpectedResponseException $exception) {
            self::assertSame(200, $exception->statusCode);
            self::assertSame($body, $exception->responseBody);
        }
    }

    public static function invalidJsonResponses(): iterable
    {
        yield 'html' => ['<html>Login</html>'];
        yield 'truncated' => ['{"version":'];
        yield 'null' => ['null'];
        yield 'scalar' => ['"healthy"'];
    }

    public function test_it_rejects_malformed_external_status_fields(): void
    {
        $payload = $this->statusPayload();
        $payload['tasks'][0]['id'] = 'not-an-integer';
        $this->expectException(UnexpectedValueException::class);
        $this
            ->client(new RecordingHttpClient($this->response($payload)))
            ->connector('source')
            ->status();
    }

    public function test_it_rejects_an_object_where_connector_names_should_be_a_list(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->client(new RecordingHttpClient($this->response(['name' => 'source'])))->connectors();
    }

    public function test_it_does_not_follow_redirects_with_credentials(): void
    {
        $factory = new HttpFactory;
        $http = new RecordingHttpClient(new Response(302, ['Location' => 'https://other.example/connectors']));
        $client = new Client('https://connect.example', $http, $factory, $factory, [
            'Authorization' => 'Bearer secret',
        ]);
        try {
            $client->connectors();
            self::fail('Expected an API exception.');
        } catch (ApiException $exception) {
            self::assertSame(302, $exception->statusCode);
            self::assertCount(1, $http->requests);
        }
    }

    #[DataProvider('invalidBaseUris')]
    public function test_it_rejects_unsafe_or_ambiguous_base_uris(string $baseUri): void
    {
        $factory = new HttpFactory;
        $this->expectException(InvalidArgumentException::class);
        new Client($baseUri, new RecordingHttpClient, $factory, $factory);
    }

    public static function invalidBaseUris(): iterable
    {
        yield 'scheme' => ['ftp://connect.example'];
        yield 'relative' => ['/connect'];
        yield 'query' => ['http://connect.example?token=x'];
        yield 'fragment' => ['http://connect.example#fragment'];
        yield 'credentials' => ['http://user:password@connect.example'];
    }

    #[DataProvider('invalidPaths')]
    public function test_it_rejects_paths_that_escape_or_change_the_request_target(string $path): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->client(new RecordingHttpClient)->request('GET', $path);
    }

    public static function invalidPaths(): iterable
    {
        yield 'absolute url' => ['https://other.example'];
        yield 'network path' => ['//other.example'];
        yield 'relative' => ['connectors'];
        yield 'query' => ['/connectors?expand=status'];
        yield 'fragment' => ['/connectors#fragment'];
        yield 'parent' => ['/../admin'];
        yield 'encoded parent' => ['/%2e%2e/admin'];
    }

    public function test_it_rejects_dot_segments_in_resource_names(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->client(new RecordingHttpClient)->connector('..');
    }

    public function test_it_rejects_negative_task_ids(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->client(new RecordingHttpClient)->connector('source')->task(-1);
    }

    public function test_it_propagates_transport_failures_unchanged(): void
    {
        $failure = new class('Connection failed') extends RuntimeException implements ClientExceptionInterface {};
        $http = $this->createMock(ClientInterface::class);
        $http->expects(self::once())->method('sendRequest')->willThrowException($failure);
        try {
            $this->client($http)->connectors();
            self::fail('Expected the HTTP client failure.');
        } catch (ClientExceptionInterface $exception) {
            self::assertSame($failure, $exception);
        }
    }

    private function client(ClientInterface $httpClient): Client
    {
        $factory = new HttpFactory;

        return new Client('http://connect.example:8083', $httpClient, $factory, $factory);
    }

    /** @param array<array-key, mixed> $payload */
    private function response(array $payload, int $status = 200): Response
    {
        return new Response(
            $status,
            ['Content-Type' => 'application/json'],
            json_encode($payload, JSON_THROW_ON_ERROR),
        );
    }

    /** @return array<array-key, mixed> */
    private function body(RequestInterface $request): array
    {
        return json_decode((string) $request->getBody(), true, flags: JSON_THROW_ON_ERROR);
    }

    /** @return list<array{string, string}> */
    private function routes(RecordingHttpClient $http): array
    {
        return array_map(static fn (RequestInterface $request) => [
            $request->getMethod(),
            $request->getUri()->getPath(),
        ], $http->requests);
    }

    /** @return array<string, mixed> */
    private function statusPayload(string $state = 'RUNNING'): array
    {
        return [
            'name' => 'source',
            'type' => 'source',
            'connector' => ['state' => $state, 'worker_id' => 'worker:8083'],
            'tasks' => [['id' => 0, 'state' => 'FAILED', 'worker_id' => 'worker:8083', 'trace' => 'Failure details']],
        ];
    }
}

final class RecordingHttpClient implements ClientInterface
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

        return array_shift($this->responses) ?? throw new LogicException('No queued response.');
    }
}

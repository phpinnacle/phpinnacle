# Franz

Franz is a framework-agnostic PHP 8.4 client for the [Apache Kafka Connect REST API](https://kafka.apache.org/42/kafka-connect/user-guide/#rest-api). Like Langcat and Canvio, it uses PSR-18 and PSR-17 interfaces, typed requests, readonly responses, and resource contexts. The application chooses the HTTP client and factories.

The name refers to Franz Kafka, the writer who also inspired Apache Kafka's name.

## Installation

```bash
composer require phpinnacle/franz
```

Install a PSR-18 HTTP client and PSR-17 factories separately. For example, with Guzzle:

```php
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Psr7\HttpFactory;
use PHPinnacle\Franz\Client;

$factory = new HttpFactory();
$connect = new Client(
    'http://localhost:8083',
    new HttpClient(),
    $factory,
    $factory,
);
```

HTTP and HTTPS base URIs are supported, including a reverse-proxy path prefix. Authentication is optional and deployment-specific. Pass headers when required:

```php
$connect = new Client(
    'https://connect.example.com',
    new HttpClient(),
    $factory,
    $factory,
    headers: ['Authorization' => 'Bearer ' . $accessToken],
);
```

For Basic authentication, use an `Authorization: Basic ...` header. Configure TLS certificates and timeouts on the injected HTTP client. Franz does not register framework providers, use global state, follow redirects, or retry requests. HTTP transport exceptions propagate unchanged.

## Connector management

Configuration keys belong to individual connector plugins, so their maps stay open and are documented as `array<string, string>`. Pass Kafka configuration values as strings, including numbers and booleans. Franz sends them without adding defaults or rewriting names. Kafka Connect performs configuration validation.

```php
use PHPinnacle\Franz\Enum\InitialState;
use PHPinnacle\Franz\Request\CreateConnectorRequest;
use PHPinnacle\Franz\Request\ConnectorConfigRequest;
use PHPinnacle\Franz\Request\PatchConnectorConfigRequest;

$config = [
    'connector.class' => 'org.apache.kafka.connect.file.FileStreamSourceConnector',
    'tasks.max' => '1',
    'file' => '/data/lessons.txt',
    'topic' => 'lessons',
];

$created = $connect->createConnector(new CreateConnectorRequest(
    name: 'lessons-source',
    config: $config,
    initialState: InitialState::Stopped,
));

$connector = $connect->connector($created->name);
$details = $connector->details();
$currentConfig = $connector->config();

// PUT replaces the complete configuration, or creates a missing connector.
$connector->updateConfig(new ConnectorConfigRequest($config));

// PATCH changes only supplied keys. A null value removes a key.
$connector->patchConfig(new PatchConnectorConfigRequest([
    'tasks.max' => '2',
    'errors.log.enable' => null,
]));
```

Omitting `initialState` lets Kafka Connect use its default `RUNNING` state. `InitialState::Paused` is also supported. Full replacement should use your authoritative configuration: server responses may mask password fields, so blindly writing a configuration read from the API can overwrite secrets.

```php
$names = $connect->connectors(); // list<string>
$worker = $connect->info();      // version, commit, kafkaClusterId, raw
$health = $connect->health();    // status, message, raw; unhealthy HTTP responses throw ApiException

// One request with expand=info&expand=status.
foreach ($connect->expandedConnectors() as $name => $entry) {
    echo $name . ': ' . $entry->status->connector->state;
}

$connector->pause();
$connector->resume();
$connector->stop();
$connector->restart();
$restartStatus = $connector->restart(includeTasks: true, onlyFailed: true);
$status = $connector->status();

foreach ($status->tasks as $task) {
    echo $task->id . ': ' . $task->state;
}

$connector->delete();
```

Lifecycle commands can be asynchronous. Their return confirms the HTTP request, not that every task has reached the target state. Read `status()` when you need the current state. `restart()` returns a `ConnectorStatusResponse` when the server supplies one, or `null` for an empty acknowledgement. Setting only `onlyFailed: true` affects the connector instance; set both flags to restart failed tasks too.

`pause()` retains task resources. `stop()` shuts tasks down and releases resources while retaining the connector configuration. `resume()` resumes either a paused or a stopped connector.

## Tasks and topic tracking

```php
$connector = $connect->connector('lessons-source');
$tasks = $connector->tasks();
$task = $connector->task(0);
$taskStatus = $task->status();
$task->restart();

$topics = $connector->topics(); // list<string>
$connector->resetTopics();
```

Task IDs are zero-based. Topic tracking reports the topics used since creation or the last tracking reset. `resetTopics()` clears that tracking set; it does not delete Kafka topics or change offsets.

## Offsets

Offset writes require a stopped connector. Stop it explicitly and verify its state before modifying offsets; Franz does not perform additional state changes or polling on your behalf.

```php
use PHPinnacle\Franz\Request\AlterConnectorOffsetsRequest;
use PHPinnacle\Franz\Value\ConnectorOffset;

$sink = $connect->connector('lessons-sink');
$sink->stop();
// Wait for status()->connector->state === 'STOPPED' in your application.

$currentOffsets = $sink->offsets(); // list<ConnectorOffset>

$result = $sink->alterOffsets(new AlterConnectorOffsetsRequest([
    new ConnectorOffset(
        partition: ['kafka_topic' => 'lessons', 'kafka_partition' => 0],
        offset: ['kafka_offset' => 100],
    ),
    new ConnectorOffset(
        partition: ['kafka_topic' => 'lessons', 'kafka_partition' => 1],
        offset: null, // Reset this partition only.
    ),
]));

// Alternatively, reset all offsets for the stopped connector.
$result = $sink->resetOffsets();
echo $result->message;
$sink->resume();
```

Source connectors define their own partition and offset fields, such as `['filename' => 'lessons.txt']` and `['position' => 30]`. `ConnectorOffset` preserves these maps, including zero values, empty JSON objects, and explicit null resets. Resetting offsets can cause records to be processed again.

## Plugins and validation

```php
use PHPinnacle\Franz\Request\ConnectorConfigRequest;

$plugins = $connect->plugins();
$allPlugins = $connect->plugins(connectorsOnly: false);
$plugin = $connect->plugin('org.apache.kafka.connect.file.FileStreamSourceConnector');
$definitions = $plugin->config(); // Server chooses the latest plugin version.
$versionedDefinitions = $plugin->config(version: '4.2.0');
$validation = $plugin->validateConfig(new ConnectorConfigRequest($config));

if (!$validation->isValid()) {
    foreach ($validation->configs as $field) {
        foreach ($field->value->errors as $error) {
            echo $field->value->name . ': ' . $error;
        }
    }
}
```

Validation errors returned with HTTP 200 are a normal typed result. Non-successful HTTP responses throw `ApiException`. Plugin listings describe the worker handling the request and can differ during rolling upgrades. `connectorsOnly: false` includes other plugin types supported by the server. A plugin's `version` is `null` when the server omits it or returns `null`.

## Log levels

```php
use PHPinnacle\Franz\Enum\LogLevel;
use PHPinnacle\Franz\Enum\LoggerScope;

$loggers = $connect->loggers();
$logger = $connect->logger('org.apache.kafka.connect');
$currentLevel = $logger->details();
$affectedNames = $logger->setLevel(LogLevel::Debug);
$logger->setLevel(LogLevel::Info, LoggerScope::Cluster);
```

Worker-scoped changes return the affected logger names. Cluster-scoped changes return `null` when acknowledged without a body. If Kafka Connect uses a separate admin listener, construct another client with that listener's base URI.

## Responses, errors, and raw requests

Response objects expose stable fields and the decoded payload through `raw`. Connector states and plugin types remain strings so servers can introduce new values. Configurations, names, topics, offsets, and logger collections use typed arrays; Kafka Connect does not paginate these endpoints.

Non-successful HTTP responses throw `PHPinnacle\Franz\Exception\ApiException` with `statusCode`, Kafka's `errorCode` when present, `responseBody`, and the decoded `response` when available. The exception message uses Kafka's error message. Rebalance conflicts (HTTP 409) and missing connectors (HTTP 404) are surfaced without automatic retries or suppressed errors.

Malformed JSON or a scalar JSON response throws `UnexpectedResponseException`, retaining HTTP status and body. Unexpected typed payload fields throw `UnexpectedValueException`. Empty successful bodies return `null` from raw requests and methods whose contract allows an empty acknowledgement.

Use `request()` for server extensions or endpoints without a typed wrapper:

```php
$statuses = $connect->request('GET', '/connectors', ['expand' => ['status']]);
```

Paths are relative to the configured base URI and start with `/`. Supply query parameters separately; list-valued parameters are emitted as repeated keys. Request bodies are JSON objects. Paths cannot specify another origin, a query string, a fragment, or parent traversal.

## API coverage and compatibility

The client covers the public connector management surface documented for Apache Kafka Connect 4.2:

| Resource | Operations |
| --- | --- |
| Worker | `GET /`; `GET /health` |
| Connectors | `GET`, `POST /connectors`; `GET`, `DELETE /connectors/{name}`; expanded listings |
| Configuration | `GET`, `PUT`, `PATCH /connectors/{name}/config` |
| Lifecycle | `GET /connectors/{name}/status`; `PUT` pause, resume, stop; `POST` restart |
| Tasks | `GET /connectors/{name}/tasks`; `GET` task status; `POST` task restart |
| Topics | `GET /connectors/{name}/topics`; `PUT /connectors/{name}/topics/reset` |
| Offsets | `GET`, `PATCH`, `DELETE /connectors/{name}/offsets` |
| Plugins | `GET /connector-plugins`; `GET` plugin configuration definitions; `PUT` configuration validation |
| Loggers | `GET /admin/loggers`; `GET`, `PUT /admin/loggers/{name}` |

Endpoint availability depends on the Kafka Connect version and server configuration. In particular, stop/offset operations, initial state, config patching, plugin options, health checks, and cluster-scoped logging require server support. Franz sends the requested operation directly and surfaces unsupported operations as API errors. Kafka broker administration, record production/consumption, and internal worker coordination endpoints are outside this package's scope.

## Runnable examples

The [examples directory](examples/) contains executable PHP scripts using Guzzle. The monorepo's development dependencies already include Guzzle. In a standalone package checkout, run `composer install` and install the example HTTP implementation with `composer require --dev guzzlehttp/guzzle`.

Run these commands from the Franz package directory against your development Kafka Connect instance:

```bash
export KAFKA_CONNECT_URL=http://localhost:8083
# Optional: export KAFKA_CONNECT_AUTHORIZATION='Bearer your-token'

php examples/inspect-cluster.php
php examples/create-file-pipeline.php
php examples/connector.php status franz-example-source
```

`create-file-pipeline.php` validates and creates a FileStream sink and source using string converters: input file → Kafka topic → output file. The worker must have the FileStream plugins installed, be able to read `/data/input.txt`, and be able to write `/data/output.txt`. The topic `franz-example-lines` must exist or broker auto-creation must be enabled. File paths belong to the Connect worker; make them accessible on every worker eligible to run these demo connectors. Append newline-terminated input to see records in the output file.

Override the defaults with `FRANZ_EXAMPLE_PREFIX`, `FRANZ_EXAMPLE_TOPIC`, `FRANZ_EXAMPLE_INPUT`, and `FRANZ_EXAMPLE_OUTPUT`. Re-running creation with existing names reports the API conflict; it does not overwrite existing connectors.

The lifecycle script operates on one connector or task:

```bash
php examples/connector.php pause franz-example-source
php examples/connector.php resume franz-example-source
php examples/connector.php restart-failed franz-example-source
php examples/connector.php task-status franz-example-source 0
php examples/connector.php restart-task franz-example-source 0
```

`restart` restarts the connector and its tasks; `restart-failed` targets failed instances. The same script supports `stop`, `reset-topics`, and `delete`. Use `status` to observe asynchronous transitions.

The included JSON files show a complete source configuration and a partial change. Adapt the full configuration to your instance before replacing it:

```bash
php examples/configure-connector.php replace franz-example-source examples/file-source.json
php examples/configure-connector.php patch franz-example-source examples/file-source.patch.json
```

The offsets script demonstrates an explicit stop → bounded wait for STOPPED → change offsets → resume workflow. It leaves the connector stopped if the change fails. `show` is read-only; the other commands alter processing checkpoints and can replay data:

```bash
php examples/offsets.php show franz-example-sink
php examples/offsets.php set-sink franz-example-sink franz-example-lines 0 100
php examples/offsets.php reset-sink-partition franz-example-sink franz-example-lines 0
php examples/offsets.php reset-all franz-example-sink
```

After experimenting, remove the demo connectors:

```bash
php examples/connector.php delete franz-example-source
php examples/connector.php delete franz-example-sink
```

Deleting connectors does not remove the demo topic or files.

## Testing

From the package root:

```bash
composer install
composer test
```

From the monorepo root after `composer install`:

```bash
vendor/bin/phpunit -c packages/franz/phpunit.xml.dist
composer phpstan:level10
```

Tests use an injected PSR-18 recording client and exercise HTTP contracts without a running Kafka cluster.

## License

The MIT License (MIT). See [License File](LICENSE.md).

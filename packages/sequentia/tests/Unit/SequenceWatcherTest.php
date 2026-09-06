<?php

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPinnacle\Sequentia\CounterStore;
use PHPinnacle\Sequentia\Sequence;
use PHPinnacle\Sequentia\SequenceWatcher;
use Symfony\Component\Process\Process;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $url = getenv('SEQUENTIA_PGSQL_URL');

    if ($url !== false && $url !== '') {
        config()->set('database.connections.sequentia_test', [
            'driver' => 'pgsql',
            'url' => $url,
        ]);
        config()->set('database.default', 'sequentia_test');
        config()->set('phpinnacle-sequentia.connection', 'sequentia_test');
    }

    Schema::create('sequences', function (Blueprint $table) {
        $table->uuid('tenant');
        $table->string('scope');
        $table->char('hash', 40);
        $table->string('key');
        $table->unsignedBigInteger('value')->default(0);
        $table->primary(['tenant', 'scope', 'hash', 'key']);
    });
});

afterEach(function () {
    Schema::dropIfExists('sequences');
    Schema::dropIfExists('sequence_records');
});

it('starts every counter at one when no rows exist', function () {
    expect(SequenceWatcher::load('orders', CarbonImmutable::now(), null))->toBe([
        'SA' => 1,
        'SY' => 1,
        'SM' => 1,
        'GA' => 1,
        'GY' => 1,
        'GM' => 1,
    ]);
});

it('rebuilds using model date casts and the current period without a creation column', function () {
    Schema::create('sequence_records', function (Blueprint $table) {
        $table->id();
        $table->timestamp('created_at');
        $table->timestamp('issued_at');
    });

    $createdAt = CarbonImmutable::now()->subYears(2);
    $issuedAt = $createdAt->subMonth();

    DB::table('sequence_records')->insert([
        'created_at' => $createdAt,
        'issued_at' => $issuedAt,
    ]);

    $standard = new WatchedSequenceRecord;
    $renamed = new RenamedSequenceRecord;
    $cast = new CastSequenceRecord;
    $undated = new UndatedSequenceRecord;

    foreach ([$standard, $renamed, $cast, $undated] as $model) {
        SequenceWatcher::register($model::class);
    }

    SequenceWatcher::rebuild();

    foreach ([
        [$standard, $createdAt],
        [$renamed,  $issuedAt],
        [$cast,     $createdAt],
        [$undated,  CarbonImmutable::now()],
    ] as [$model, $date]) {
        expect(SequenceWatcher::load($model::class, $date, null))->toMatchArray([
            'SA' => 2,
            'SY' => 2,
            'SM' => 2,
            'GA' => 2,
            'GY' => 2,
            'GM' => 2,
        ]);
    }
});

it('stores independent scoped and global counters atomically', function (bool $stringify) {
    DB::connection()->getPdo()->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, $stringify);

    $date = CarbonImmutable::parse('2026-07-17');
    $record = new WatchedSequenceRecord;
    $firstIssuer = $record->newInstance(['issuer_id' => 10]);
    $secondIssuer = $record->newInstance(['issuer_id' => 20]);
    $scope = $record::class;

    SequenceWatcher::store($firstIssuer, $date, null, ['issuer_id']);
    SequenceWatcher::store($firstIssuer, $date, null, ['issuer_id']);
    SequenceWatcher::store($secondIssuer, $date, null, ['issuer_id']);

    $first = SequenceWatcher::load(
        $scope,
        $date,
        null,
        ['issuer_id' => 10],
    );
    $second = SequenceWatcher::load(
        $scope,
        $date,
        null,
        ['issuer_id' => 20],
    );

    expect($first)
        ->toMatchArray(['SA' => 3, 'SY' => 3, 'SM' => 3])
        ->and($second)
        ->toMatchArray(['SA' => 2, 'SY' => 2, 'SM' => 2])
        ->and($first)
        ->toMatchArray(['GA' => 4, 'GY' => 4, 'GM' => 4]);
})->with([false, true]);

class WatchedSequenceRecord extends Model
{
    protected $table = 'sequence_records';

    protected $guarded = [];
}

it('counts global counters once for multiple schemes and repeated registration', function () {
    Schema::create('sequence_records', function (Blueprint $table) {
        $table->id();
        $table->string('issuer');
        $table->string('category');
        $table->timestamps();
    });

    SequenceWatcher::register(WatchedSequenceRecord::class, scheme: ['issuer']);
    SequenceWatcher::register(WatchedSequenceRecord::class, scheme: ['issuer']);
    SequenceWatcher::register(WatchedSequenceRecord::class, scheme: ['category']);

    WatchedSequenceRecord::create(['issuer' => 'company', 'category' => 'invoice']);

    foreach ([['issuer' => 'company'], ['category' => 'invoice']] as $bucket) {
        expect(SequenceWatcher::load(WatchedSequenceRecord::class, now(), null, $bucket))
            ->toBe(['SA' => 2, 'SY' => 2, 'SM' => 2, 'GA' => 2, 'GY' => 2, 'GM' => 2]);
    }
});

it('reattaches registrations after replacing the event dispatcher', function () {
    SequenceWatcher::register(WatchedSequenceRecord::class, scheme: ['issuer']);
    $original = Model::getEventDispatcher();

    try {
        Model::setEventDispatcher(new Dispatcher(app()));
        SequenceWatcher::register(WatchedSequenceRecord::class, scheme: ['category']);
        SequenceWatcher::register(WatchedSequenceRecord::class, scheme: ['category']);
        $record = new WatchedSequenceRecord(['issuer' => 'company', 'category' => 'invoice']);
        Model::getEventDispatcher()->dispatch('eloquent.created: ' . WatchedSequenceRecord::class, $record);

        expect(SequenceWatcher::load(WatchedSequenceRecord::class, now(), null, ['category' => 'invoice']))
            ->toBe(['SA' => 2, 'SY' => 2, 'SM' => 2, 'GA' => 2, 'GY' => 2, 'GM' => 2])
            ->and(SequenceWatcher::load(WatchedSequenceRecord::class, now(), null, ['issuer' => 'company'])['SA'])
            ->toBe(1);
    } finally {
        Model::setEventDispatcher($original);
    }
});

it('rebuilds the same records twice without increasing their counters', function () {
    Schema::create('sequence_records', function (Blueprint $table) {
        $table->id();
        $table->string('issuer');
        $table->timestamps();
    });

    SequenceWatcher::register(WatchedSequenceRecord::class, scheme: ['issuer']);
    WatchedSequenceRecord::create(['issuer' => 'company']);

    SequenceWatcher::rebuild();
    SequenceWatcher::rebuild();

    expect(SequenceWatcher::load(WatchedSequenceRecord::class, now(), null, ['issuer' => 'company']))
        ->toBe(['SA' => 2, 'SY' => 2, 'SM' => 2, 'GA' => 2, 'GY' => 2, 'GM' => 2]);

    WatchedSequenceRecord::create(['issuer' => 'company']);

    expect(SequenceWatcher::load(WatchedSequenceRecord::class, now(), null, ['issuer' => 'company']))
        ->toBe(['SA' => 3, 'SY' => 3, 'SM' => 3, 'GA' => 3, 'GY' => 3, 'GM' => 3]);
});

class RenamedSequenceRecord extends WatchedSequenceRecord
{
    public const string CREATED_AT = 'issued_at';
}

class CastSequenceRecord extends WatchedSequenceRecord
{
    public $timestamps = false;

    protected $casts = ['created_at' => 'immutable_datetime'];
}

class UndatedSequenceRecord extends WatchedSequenceRecord
{
    public const null CREATED_AT = null;

    public $timestamps = false;
}

it('reconstructs registered keys across tenants and periods while preserving other counters', function () {
    Schema::create('sequence_records', function (Blueprint $table) {
        $table->id();
        $table->uuid('tenant_id');
        $table->string('issuer');
        $table->string('category');
        $table->timestamps();
    });

    $firstTenant = '00000000-0000-0000-0000-000000000001';
    $secondTenant = '00000000-0000-0000-0000-000000000002';
    $absentTenant = '00000000-0000-0000-0000-000000000003';
    $january = CarbonImmutable::parse('2026-01-17');
    $february = $january->addMonth();
    $previousYear = $january->subYear();

    foreach ([['issuer'], ['category'], []] as $scheme) {
        SequenceWatcher::register(WatchedSequenceRecord::class, tenant: 'tenant_id', scheme: $scheme);
    }

    foreach ([
        [$firstTenant,  $january],
        [$firstTenant,  $february],
        [$firstTenant,  $previousYear],
        [$secondTenant, $january],
    ] as [$tenant, $date]) {
        $this->travelTo($date);
        WatchedSequenceRecord::create(['tenant_id' => $tenant, 'issuer' => 'company', 'category' => 'invoice']);
    }

    $absent = new WatchedSequenceRecord(['tenant_id' => $absentTenant, 'issuer' => 'company']);
    SequenceWatcher::store($absent, $january, 'tenant_id', ['issuer']);
    $otherScope = new RenamedSequenceRecord(['tenant_id' => $firstTenant, 'issuer' => 'company']);
    SequenceWatcher::store($otherScope, $january, 'tenant_id', ['issuer']);
    $unregistered = new WatchedSequenceRecord(['tenant_id' => $firstTenant, 'external' => 'legacy']);
    SequenceWatcher::store($unregistered, $january, 'tenant_id', ['external']);

    // Simulate inflated historical counters from earlier rebuilds.
    DB::table('sequences')->increment('value', 10);
    $untouched = DB::table('sequences')
        ->where(function ($query) use ($absentTenant) {
            $query->where('tenant', $absentTenant)->orWhere('scope', RenamedSequenceRecord::class);
        })
        ->get()
        ->all();

    SequenceWatcher::rebuild();
    $once = DB::table('sequences')
        ->orderBy('tenant')
        ->orderBy('scope')
        ->orderBy('hash')
        ->orderBy('key')
        ->get()
        ->all();
    SequenceWatcher::rebuild();

    expect(
        DB::table('sequences')
            ->orderBy('tenant')
            ->orderBy('scope')
            ->orderBy('hash')
            ->orderBy('key')
            ->get()
            ->all(),
    )
        ->toEqual($once);

    foreach ([['issuer' => 'company'], ['category' => 'invoice'], []] as $bucket) {
        expect(SequenceWatcher::load(WatchedSequenceRecord::class, $january, $firstTenant, $bucket))
            ->toBe(['SA' => 4, 'SY' => 3, 'SM' => 2, 'GA' => 4, 'GY' => 3, 'GM' => 2])
            ->and(SequenceWatcher::load(WatchedSequenceRecord::class, $previousYear, $firstTenant, $bucket))
            ->toBe(['SA' => 4, 'SY' => 2, 'SM' => 2, 'GA' => 4, 'GY' => 2, 'GM' => 2])
            ->and(SequenceWatcher::load(WatchedSequenceRecord::class, $january, $secondTenant, $bucket))
            ->toBe(['SA' => 2, 'SY' => 2, 'SM' => 2, 'GA' => 2, 'GY' => 2, 'GM' => 2]);
    }

    expect(
        DB::table('sequences')
            ->where(function ($query) use ($absentTenant) {
                $query->where('tenant', $absentTenant)->orWhere('scope', RenamedSequenceRecord::class);
            })
            ->get()
            ->all(),
    )
        ->toEqual($untouched)
        ->and(SequenceWatcher::load(WatchedSequenceRecord::class, $january, $firstTenant, [
            'external' => 'legacy',
        ])['SA'])
        ->toBe(12);
});

it('preserves hash serialization and reads prospective values on the configured connection', function () {
    $date = CarbonImmutable::parse('2026-01-17');
    $bucket = ['issuer' => 'company', 'number' => 10];
    $record = new WatchedSequenceRecord($bucket);
    SequenceWatcher::store($record, $date, null, array_keys($bucket));

    expect(DB::table('sequences')->where('key', 'SM')->value('hash'))
        ->toBe(sha1(serialize(['bucket' => $bucket, 'period' => '2026-01'])))
        ->and(DB::table('sequences')->where('key', 'GA')->value('hash'))
        ->toBe(sha1(serialize(['bucket' => [], 'period' => '0'])));

    $connection = DB::getDefaultConnection();
    config()->set('phpinnacle-sequentia.connection', $connection);
    config()->set('database.connections.other', ['driver' => 'sqlite', 'database' => ':memory:']);
    DB::setDefaultConnection('other');

    try {
        $sequence = Sequence::create('[SA]-[SY]-[SM]-[GA]-[GY]-[GM]', $record::class, $bucket)->on($date);
        expect($sequence->get())->toBe('2-2-2-2-2-2')->and($sequence->get())->toBe('2-2-2-2-2-2');
        SequenceWatcher::store($record, $date, null, array_keys($bucket));
        expect($sequence->get())->toBe('3-3-3-3-3-3');
    } finally {
        DB::setDefaultConnection($connection);
    }
});

it('rolls back every replacement chunk if a database write fails', function () {
    $store = CounterStore::configured();
    $rows = [];

    for ($index = 0; $index < 40; ++$index) {
        $rows = array_replace($rows, $store->rows('orders', now(), SequenceWatcher::DEFAULT_TENANT, [
            'issuer' => $index,
        ]));
    }

    $store->replace($rows);
    $previous = DB::table('sequences')->get()->all();
    $writes = 0;
    DB::listen(function ($query) use (&$writes) {
        if (str_starts_with($query->sql, 'insert') && ++$writes === 2) {
            throw new RuntimeException('Interrupted replacement');
        }
    });

    foreach ($rows as &$row) {
        $row['value'] = 10;
    }
    unset($row);

    expect(fn () => $store->replace($rows))->toThrow(RuntimeException::class, 'Interrupted replacement');
    expect(DB::table('sequences')->get()->all())->toEqual($previous);
});

it('increments concurrently on PostgreSQL without losing updates', function () {
    if (!getenv('SEQUENTIA_PGSQL_URL')) {
        $this->markTestSkipped('Set SEQUENTIA_PGSQL_URL to a dedicated PostgreSQL test database.');
    }

    $autoload = var_export(getcwd() . '/vendor/autoload.php', true);
    $configuration = var_export(config('database.connections.sequentia_test'), true);
    $script = <<<PHP
        require {$autoload};
        \$database = new Illuminate\Database\Capsule\Manager();
        \$database->addConnection({$configuration});
        \$store = new PHPinnacle\Sequentia\CounterStore(\$database->getConnection());
        \$rows = \$store->rows('concurrent', new DateTimeImmutable('2026-01-17'), PHPinnacle\Sequentia\SequenceWatcher::DEFAULT_TENANT, []);
        for (\$index = 0; \$index < 100; ++\$index) {
            \$store->increment(\$rows);
        }
        PHP;
    $processes = [];

    for ($index = 0; $index < 4; ++$index) {
        $process = new Process([PHP_BINARY, '-r', $script]);
        $process->start();
        $processes[] = $process;
    }

    foreach ($processes as $process) {
        $process->wait();
        expect($process->getExitCode())->toBe(0, $process->getErrorOutput());
    }

    expect(SequenceWatcher::load('concurrent', CarbonImmutable::parse('2026-01-17'), null))
        ->toBe(['SA' => 401, 'SY' => 401, 'SM' => 401, 'GA' => 401, 'GY' => 401, 'GM' => 401]);
});

<?php

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPinnacle\Sequentia\SequenceWatcher;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
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

    $standard = new class extends Model {
        protected $table = 'sequence_records';
    };
    $renamed = new class extends Model {
        public const string CREATED_AT = 'issued_at';

        protected $table = 'sequence_records';
    };
    $cast = new class extends Model {
        public $timestamps = false;

        protected $table = 'sequence_records';

        protected $casts = ['created_at' => 'immutable_datetime'];
    };
    $undated = new class extends Model {
        public const null CREATED_AT = null;

        public $timestamps = false;

        protected $table = 'sequence_records';
    };

    $registered = new ReflectionProperty(SequenceWatcher::class, 'registered');
    $watched = new ReflectionProperty(SequenceWatcher::class, 'watched');
    $previousRegistered = $registered->getValue();
    $previousWatched = $watched->getValue();

    try {
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
    } finally {
        $registered->setValue(null, $previousRegistered);
        $watched->setValue(null, $previousWatched);
    }
});

it('stores independent scoped and global counters atomically', function (bool $stringify) {
    DB::connection()->getPdo()->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, $stringify);

    $date = CarbonImmutable::parse('2026-07-17');
    $record = new class extends Model {
        protected $guarded = [];
    };
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

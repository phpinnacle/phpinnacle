<?php

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
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
});

it('stores independent scoped and global counters atomically', function () {
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
});

<?php

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPinnacle\Tags\Models\Tag;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $url = getenv('TAGS_PGSQL_URL');
    config()->set(
        'database.connections.tags_test',
        $url
            ? ['driver' => 'pgsql', 'url' => $url]
            : ['driver' => 'sqlite', 'database' => ':memory:'],
    );
    config()->set('phpinnacle-tags.connection', 'tags_test');
    $connection = DB::connection('tags_test');
    $connection->beginTransaction();
    $schema = $connection->getSchemaBuilder();
    $schema->create('tags', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->string('name');
        $table->string('type');
        $table->string('color');
        $table->timestamps();
        $table->unique(['name', 'type']);
    });
    $schema->create('taggables', function (Blueprint $table) {
        $table->uuid('tag_id');
        $table->uuid('taggable_id');
        $table->primary(['tag_id', 'taggable_id']);
        $table->foreign('tag_id')->references('id')->on('tags')->cascadeOnDelete();
    });
    Schema::create('connection_tagged_records', function (Blueprint $table) {
        $table->uuid('id')->primary();
    });
});

afterEach(function () {
    DB::connection('tags_test')->rollBack();
});

it('assigns reads and removes tags on their configured connection', function () {
    $selected = ConnectionTaggedRecord::query()->create();
    $other = ConnectionTaggedRecord::query()->create();
    $otherType = new OtherConnectionTaggedRecord;
    $otherType->id = $selected->id;
    Tag::manage(collect([$selected, $other]), ['Featured']);
    Tag::manage($selected, ['Featured', 'Retained']);
    Tag::manage($selected, ['Featured']);
    Tag::manage($otherType, ['Featured']);

    expect(Tag::retrieve($selected)->all())
        ->toEqualCanonicalizing(['Featured', 'Retained'])
        ->and(Tag::retrieve($otherType)->all())
        ->toBe(['Featured'])
        ->and(DB::connection('tags_test')->table('taggables')->count())
        ->toBe(4);

    Tag::manage($selected, ['Featured'], drop: true);
    expect(Tag::retrieve($selected)->all())->toBe(['Retained'])->and(Tag::retrieve($other)->all())->toBe(['Featured']);
    Tag::clear($selected);
    expect(Tag::retrieve($selected)->all())
        ->toBe([])
        ->and(Tag::retrieve($otherType)->all())
        ->toBe(['Featured'])
        ->and(Tag::retrieve($other)->all())
        ->toBe(['Featured']);
});

it('rolls back definitions and pivot writes together when a later type fails', function () {
    $selected = ConnectionTaggedRecord::query()->create();
    $otherType = new OtherConnectionTaggedRecord;
    $otherType->id = $selected->id;
    Tag::creating(function (Tag $tag) {
        if ($tag->type === OtherConnectionTaggedRecord::class) {
            throw new RuntimeException('Interrupted assignment');
        }
    });

    expect(fn () => Tag::manage(collect([$selected, $otherType]), ['New']))
        ->toThrow(RuntimeException::class, 'Interrupted assignment');
    expect(Tag::query()->count())->toBe(0)->and(DB::connection('tags_test')->table('taggables')->count())->toBe(0);
});

it('does not assign to missing or unselected source records', function () {
    $selected = ConnectionTaggedRecord::query()->create();
    $other = ConnectionTaggedRecord::query()->create();
    $selected->delete();
    Tag::manage($selected, ['Featured']);
    Tag::manage(collect(), ['Unused']);
    Tag::manage($other, []);

    expect(DB::connection('tags_test')->table('taggables')->count())
        ->toBe(0)
        ->and(Tag::query()->pluck('name')->all())
        ->toBe(['Featured']);
});

class ConnectionTaggedRecord extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $table = 'connection_tagged_records';
}

class OtherConnectionTaggedRecord extends ConnectionTaggedRecord {}

it('checks source existence independently for models on different connections', function () {
    config()->set('database.connections.other_source', ['driver' => 'sqlite', 'database' => ':memory:']);
    Schema::connection('other_source')->create('connection_tagged_records', function (Blueprint $table) {
        $table->uuid('id')->primary();
    });
    $local = ConnectionTaggedRecord::query()->create();
    $remote = new ConnectionTaggedRecord()->setConnection('other_source');
    $remote->save();

    Tag::manage(collect([$local, $remote]), ['Shared']);

    expect(Tag::retrieve($local)->all())
        ->toBe(['Shared'])
        ->and(Tag::retrieve($remote)->all())
        ->toBe(['Shared'])
        ->and(DB::connection('tags_test')->table('taggables')->count())
        ->toBe(2);
});

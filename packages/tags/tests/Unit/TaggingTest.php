<?php

use Filament\Panel;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPinnacle\Tags\Actions\TagsBulkAction;
use PHPinnacle\Tags\Filters\TagsFilter;
use PHPinnacle\Tags\Models\Tag;
use PHPinnacle\Tags\TagsPlugin;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    (require __DIR__ . '/../../database/migrations/create_tags_tables.php')->up();

    Schema::create('tagged_records', function (Blueprint $table) {
        $table->uuid('id')->primary();
    });

    $this->previousMorphMap = Relation::morphMap();
});

afterEach(function () {
    Relation::morphMap($this->previousMorphMap, merge: false);
});

it('selects options for the table model and respects explicit tag types', function (bool $alias, bool $override) {
    if ($alias) {
        Relation::morphMap(['tagged-record' => TaggedRecord::class]);
    }

    $type = $override ? 'explicit-type' : new TaggedRecord()->getMorphClass();
    $tag = Tag::query()->create(['name' => 'Featured', 'type' => $type]);
    Tag::query()->create(['name' => 'Other', 'type' => 'other-type']);

    $table = Table::make(Mockery::mock(HasTable::class))->query(TaggedRecord::query());
    $filter = TagsFilter::make()->table($table);

    if ($override) {
        $filter->model($type);
    }

    expect($filter->getFormSchema()[0]->getOptions())->toBe([$tag->id => 'Featured']);
})->with([[false, false], [true, false], [true, true]]);

it('filters through the registered relationship and leaves empty selections unrestricted', function () {
    TagsPlugin::make()
        ->models(fn () => TaggedRecord::class)
        ->boot(Panel::make());
    $tagged = TaggedRecord::query()->create();
    $untagged = TaggedRecord::query()->create();
    $tag = Tag::query()->create(['name' => 'Featured', 'type' => TaggedRecord::class]);

    DB::table('taggables')->insert(['tag_id' => $tag->id, 'taggable_id' => $tagged->getKey()]);

    $filter = TagsFilter::make()->visible();

    expect(
        $filter
            ->apply(TaggedRecord::query(), ['tags' => [$tag->id]])
            ->get()
            ->modelKeys(),
    )->toBe([$tagged->getKey()]);
    expect($filter->apply(TaggedRecord::query(), ['tags' => []])->count())->toBe(2);
    expect($filter->apply(TaggedRecord::query(), [])->count())->toBe(2);
    expect(Tag::retrieve($tagged)->all())->toBe(['Featured']);
    expect(Tag::retrieve($untagged)->all())->toBe([]);
});

it('builds bulk assignments with reused tag IDs and selected record keys', function () {
    $records = collect([TaggedRecord::query()->create(), TaggedRecord::query()->create()]);
    $tag = Tag::query()->create(['name' => 'Featured', 'type' => TaggedRecord::class]);

    $database = Mockery::mock(DB::getFacadeRoot())->makePartial();
    DB::swap($database);

    $database
        ->shouldReceive('statement')
        ->once()
        ->withArgs(function (string $sql, array $bindings) use ($records, $tag) {
            expect($bindings)->toBe($records->pluck('id')->all());
            expect($sql)->toContain("'{$tag->id}'::uuid", 'ON CONFLICT DO NOTHING', '"tagged_records"', '?, ?');

            return true;
        })
        ->andReturnTrue();

    $action = TagsBulkAction::make()->getActionFunction();
    $action($records, ['tags' => [' Featured ', 'Featured']], []);

    expect(Tag::query()->count())->toBe(1);
    expect(Tag::list($tag->id)->all())->toBe([$tag->id => 'Featured']);
});

it('drops selected tags only from selected records through the bulk action', function () {
    $selected = TaggedRecord::query()->create();
    $other = TaggedRecord::query()->create();
    $featured = Tag::query()->create(['name' => 'Featured', 'type' => TaggedRecord::class]);
    $retained = Tag::query()->create(['name' => 'Retained', 'type' => TaggedRecord::class]);

    DB::table('taggables')->insert([
        ['tag_id' => $featured->id, 'taggable_id' => $selected->getKey()],
        ['tag_id' => $retained->id, 'taggable_id' => $selected->getKey()],
        ['tag_id' => $featured->id, 'taggable_id' => $other->getKey()],
    ]);

    $action = TagsBulkAction::make()->getActionFunction();
    $action(collect([$selected]), ['tags' => ['Featured']], ['drop' => true]);

    expect(Tag::retrieve($selected)->all())->toBe(['Retained']);
    expect(Tag::retrieve($other)->all())->toBe(['Featured']);
    expect(Tag::query()->count())->toBe(2);
});

class TaggedRecord extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $table = 'tagged_records';
}

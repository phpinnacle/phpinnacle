<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PHPinnacle\Chronica\Models\Activity;
use PHPinnacle\Chronica\Timeline;
use PHPinnacle\Chronica\Timeline\Attribute;
use PHPinnacle\Chronica\Timeline\Relation;
use Tests\TestCase;

uses(TestCase::class);

final class TimelineFixture extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    public function children(): HasMany
    {
        return $this->hasMany(RelatedTimelineFixture::class);
    }
}

final class RelatedTimelineFixture extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';
}

beforeEach(function () {
    Schema::create('timeline_fixtures', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->timestamps();
    });

    Schema::create('related_timeline_fixtures', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->uuid('timeline_fixture_id');
        $table->string('name');
        $table->timestamps();
    });

    Schema::create('activity_log', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->string('log_name')->nullable();
        $table->text('description');
        $table->string('event')->nullable();
        $table->nullableUuidMorphs('subject');
        $table->nullableUuidMorphs('causer');
        $table->json('attribute_changes')->nullable();
        $table->json('properties')->nullable();
        $table->timestamps();
    });
});

it('renders the configured activity history for the current record', function () {
    $record = new TimelineFixture;
    $record->id = 'subject-id';
    $record->save();

    Activity::query()->create([
        'log_name' => TimelineFixture::class,
        'description' => 'updated',
        'event' => 'updated',
        'subject_type' => $record->getMorphClass(),
        'subject_id' => $record->getKey(),
        'attribute_changes' => [
            'attributes' => ['status' => 'paid'],
            'old' => ['status' => 'pending'],
        ],
    ]);

    $html = Timeline::make(TimelineFixture::class)
        ->attributes(
            Attribute::make('status')
                ->label('Order status')
                ->formatter(fn (?string $value) => strtoupper($value ?? '')),
        )
        ->render($record)
        ->render();

    expect($html)
        ->toContain('updated')
        ->toContain('Order status')
        ->toContain('PENDING')
        ->toContain('PAID');
});

it('does not render activity from another record', function () {
    $record = new TimelineFixture;
    $record->id = 'subject-id';
    $record->save();

    Activity::query()->create([
        'log_name' => TimelineFixture::class,
        'description' => 'unrelated activity',
        'event' => 'updated',
        'subject_type' => $record->getMorphClass(),
        'subject_id' => 'another-id',
    ]);

    $html = Timeline::make(TimelineFixture::class)->render($record)->render();

    expect($html)
        ->toContain(__('phpinnacle-chronica::messages.empty.heading'))
        ->not->toContain('unrelated activity');
});

it('includes configured related record activity', function () {
    $record = new TimelineFixture;
    $record->id = 'subject-id';
    $record->save();

    $related = new RelatedTimelineFixture;
    $related->id = 'related-id';
    $related->timeline_fixture_id = $record->getKey();
    $related->name = 'First line';
    $related->save();

    Activity::query()->create([
        'log_name' => TimelineFixture::class,
        'description' => 'created',
        'event' => 'created',
        'subject_type' => $related->getMorphClass(),
        'subject_id' => $related->getKey(),
    ]);

    $html = Timeline::make(TimelineFixture::class)
        ->relations(Relation::make('children', RelatedTimelineFixture::class)->label('Line')->title('name'))
        ->render($record)
        ->render();

    expect($html)
        ->toContain('created')
        ->toContain('Line · First line');
});

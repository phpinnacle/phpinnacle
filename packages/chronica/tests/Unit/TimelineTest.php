<?php

use Filament\Actions\Action;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Casts\Attribute as EloquentAttribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use PHPinnacle\Chronica\Concerns\HasHistory;
use PHPinnacle\Chronica\Models\Activity;
use PHPinnacle\Chronica\Timeline;
use PHPinnacle\Chronica\Timeline\Attribute;
use PHPinnacle\Chronica\Timeline\Relation;
use Tests\TestCase;

use function Filament\Support\generate_icon_html;

uses(TestCase::class);

enum TimelineState: string implements HasColor, HasIcon, HasLabel
{
    case Reviewing = 'reviewing';

    public function getColor(): string
    {
        return 'warning';
    }

    public function getIcon(): string
    {
        return 'heroicon-o-clock';
    }

    public function getLabel(): string
    {
        return 'Awaiting review';
    }
}

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

final class PresentableTimelineFixture extends Model
{
    public $incrementing = false;

    protected $table = 'timeline_fixtures';

    protected $keyType = 'string';

    public function status(): EloquentAttribute
    {
        return EloquentAttribute::get(fn (?string $value) => $value !== null ? TimelineState::from($value) : null);
    }
}

/**
 * @property string $id
 * @property string $status
 */
final class RevertTimelineFixture extends Model
{
    use HasHistory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['status'];
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

    Schema::create('revert_timeline_fixtures', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->string('status');
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
        ->compact()
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
        ->toContain('<details')
        ->toContain('<summary')
        ->toContain('bg-white')
        ->toContain('space-y-4')
        ->toContain('py-2')
        ->toContain('Changes')
        ->toContain('<del')
        ->toContain('PENDING')
        ->toContain('<ins')
        ->toContain('PAID');
});

it('hides excluded and empty initial values when configured', function () {
    $record = new TimelineFixture;
    $record->id = 'subject-id';
    $record->save();

    Activity::query()->create([
        'log_name' => TimelineFixture::class,
        'description' => 'created',
        'event' => 'created',
        'subject_type' => $record->getMorphClass(),
        'subject_id' => $record->getKey(),
        'attribute_changes' => [
            'attributes' => [
                'nullable' => null,
                'blank' => ' ',
                'empty_list' => [],
                'enabled' => false,
                'position' => 0,
                'sort' => 1,
                'cleared' => null,
            ],
            'old' => ['cleared' => 'value'],
        ],
    ]);

    $visibleHtml = Timeline::make(TimelineFixture::class)->render($record)->render();
    $html = Timeline::make(TimelineFixture::class)
        ->exclude('sort')
        ->hideEmptyValues()
        ->render($record)
        ->render();

    expect($visibleHtml)
        ->toContain('Nullable')
        ->toContain('Blank')
        ->toContain('Empty List')
        ->toContain('Sort');

    expect($html)
        ->not->toContain('Nullable')
        ->not->toContain('Blank')
        ->not->toContain('Empty List')
        ->not->toContain('Sort');

    expect($html)
        ->toContain('Enabled')
        ->toContain('false')
        ->toContain('Position')
        ->toMatch('/>\s*0\s*</')
        ->toContain('Cleared')
        ->toContain('value');
});

it('renders custom values and configurable state icons', function () {
    $record = new TimelineFixture;
    $record->id = 'subject-id';
    $record->save();

    Activity::query()->create([
        'log_name' => TimelineFixture::class,
        'description' => 'created',
        'event' => 'created',
        'subject_type' => $record->getMorphClass(),
        'subject_id' => $record->getKey(),
        'attribute_changes' => [
            'attributes' => [
                'country' => 'ZZ',
                'enabled' => true,
                'archived' => false,
                'removed_at' => null,
            ],
        ],
    ]);

    $timeline = Timeline::make(TimelineFixture::class)
        ->attribute('country', fn (?string $value) => $value === 'ZZ' ? 'Unknown' : $value)
        ->boolean()
        ->nullIcon();
    $defaultHtml = $timeline->render($record)->render();
    $html = $timeline
        ->trueIcon(Heroicon::OutlinedCheckBadge)
        ->falseIcon(Heroicon::OutlinedXMark)
        ->trueColor('info')
        ->falseColor('warning')
        ->nullIcon(Heroicon::OutlinedQuestionMarkCircle)
        ->render($record)
        ->render();

    foreach ([Heroicon::OutlinedCheckCircle, Heroicon::OutlinedXCircle, Heroicon::OutlinedMinusCircle] as $icon) {
        expect($defaultHtml)->toContain(Str::between(generate_icon_html($icon)->toHtml(), '>', '</svg>'));
    }

    foreach ([Heroicon::OutlinedCheckBadge, Heroicon::OutlinedXMark, Heroicon::OutlinedQuestionMarkCircle] as $icon) {
        expect($html)->toContain(Str::between(generate_icon_html($icon)->toHtml(), '>', '</svg>'));
    }

    expect($html)
        ->toContain('Unknown')
        ->toContain('aria-label="true"')
        ->toContain('aria-label="false"')
        ->toContain('aria-label="—"')
        ->toContain('--color-600:var(--info-600)')
        ->toContain('--color-600:var(--warning-600)')
        ->toContain('--color-600:var(--gray-600)');
});

it('renders labels, icons, and colors from Filament contracts', function () {
    $record = new PresentableTimelineFixture;
    $record->id = 'subject-id';
    $record->save();

    Activity::query()->create([
        'log_name' => PresentableTimelineFixture::class,
        'description' => 'created',
        'event' => 'created',
        'subject_type' => $record->getMorphClass(),
        'subject_id' => $record->getKey(),
        'attribute_changes' => [
            'attributes' => [
                'status' => [
                    'id' => TimelineState::Reviewing->value,
                    'label' => 'Serialized label',
                ],
            ],
        ],
    ]);

    $html = Timeline::make(PresentableTimelineFixture::class)->render($record)->render();

    expect($html)
        ->toContain('Awaiting review')
        ->not
        ->toContain('Serialized label')
        ->toContain(Str::between(generate_icon_html('heroicon-o-clock')->toHtml(), '>', '</svg>'))
        ->toContain('fi-color-warning');
});

it('reverts an authorized update and records the reversal', function () {
    config()->set('activitylog.activity_model', Activity::class);

    $record = new RevertTimelineFixture;
    $record->id = 'subject-id';
    $record->status = 'pending';
    $record->save();
    $record->update(['status' => 'paid']);

    $activity = Activity::query()
        ->where('subject_type', $record->getMorphClass())
        ->where('event', 'updated')
        ->sole();
    $timeline = Timeline::make(RevertTimelineFixture::class)->revertable();

    Gate::define('update', fn (?Authenticatable $user, Model $subject) => false);

    expect(fn () => $timeline->revert($record, $activity->getKey()))
        ->toThrow(AuthorizationException::class)
        ->and($record->refresh()->status)
        ->toBe('paid');

    Gate::define('update', fn (?Authenticatable $user, Model $subject) => true);

    $html = $timeline
        ->render($record, Action::make('revert_activity')->label('Revert'))
        ->render();

    expect($html)
        ->toContain('Revert')
        ->toContain((string) $activity->getKey());

    $timeline->revert($record, $activity->getKey());

    $reversal = Activity::query()
        ->where('subject_type', $record->getMorphClass())
        ->where('event', 'updated')
        ->whereKeyNot($activity->getKey())
        ->sole();

    expect($record->refresh()->status)
        ->toBe('pending')
        ->and($reversal->attribute_changes?->get('old')['status'])
        ->toBe('paid')
        ->and($reversal->attribute_changes?->get('attributes')['status'])
        ->toBe('pending');
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

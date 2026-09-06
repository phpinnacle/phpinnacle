<?php

namespace PHPinnacle\Comments\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;

/**
 * @property string $id
 * @property int|string $author_id
 * @property string $subject_type
 * @property string $subject_id
 * @property ?string $parent_id
 * @property string $text
 * @property ?CarbonImmutable $edited_at
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 */
class Comment extends Model
{
    use HasUuids;
    use MassPrunable;

    public $timestamps = true;

    protected $table = 'comments';

    protected $fillable = [
        'author_id',
        'subject_type',
        'subject_id',
        'parent_id',
        'text',
    ];

    public static function count(Model $record): int
    {
        return static::forSubject($record)->count();
    }

    /**
     * @return Builder<static>
     */
    public static function forSubject(Model $record): Builder
    {
        return static::query()
            ->where([
                'subject_type' => $record->getMorphClass(),
                'subject_id' => $record->getKey(),
            ]);
    }

    /**
     * @return Collection<int, static>
     */
    public static function list(Model $record): Collection
    {
        return static::forSubject($record)
            ->with(['author', 'parent.author'])
            ->latest()
            ->get();
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function author(): BelongsTo
    {
        /** @var class-string<Model> $model */
        $model = config('phpinnacle-comments.user.model');

        return $this->belongsTo($model, 'author_id');
    }

    public function getConnectionName(): ?string
    {
        // @mago-expect lint:inline-variable-return
        /** @var ?string $connection */
        $connection = config('phpinnacle-comments.connection', parent::getConnectionName());

        return $connection;
    }

    /**
     * @return array<int, string>
     */
    public function mentionedUserIds(): array
    {
        preg_match_all(
            '/<span(?=[^>]*data-type="mention")(?=[^>]*data-id="([^"]+)")[^>]*>/',
            $this->text,
            $matches,
        );

        return array_values(array_unique(array_map(html_entity_decode(...), $matches[1])));
    }

    /**
     * @return BelongsTo<self, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        /** @var int $days */
        $days = config('phpinnacle-comments.prune', 365);

        return static::query()->where('created_at', '<=', now()->subDays($days));
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    protected function casts(): array
    {
        return [
            'edited_at' => 'immutable_datetime',
        ];
    }
}

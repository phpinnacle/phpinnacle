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
 * @property string $author_id
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
        return static::query()
            ->forSubject($record)
            ->count();
    }

    public static function list(Model $record): Collection
    {
        return static::query()
            ->forSubject($record)
            ->with(['author', 'parent.author'])
            ->latest()
            ->get();
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(config('phpinnacle-comments.user.model'), 'author_id');
    }

    public function getConnectionName(): ?string
    {
        return config('phpinnacle-comments.connection', parent::getConnectionName());
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

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function prunable(): Builder
    {
        $days = config('phpinnacle-comments.prune', 365);

        return static::query()->where('created_at', '<=', now()->subDays($days));
    }

    public function scopeForSubject(Builder $query, Model $record): Builder
    {
        return $query->where([
            'subject_type' => $record->getMorphClass(),
            'subject_id' => $record->getKey(),
        ]);
    }

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

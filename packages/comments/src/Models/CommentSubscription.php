<?php

namespace PHPinnacle\Comments\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int|string $user_id
 * @property string $subject_type
 * @property string $subject_id
 */
class CommentSubscription extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $table = 'comment_subscriptions';

    protected $fillable = [
        'user_id',
        'subject_type',
        'subject_id',
    ];

    public function getConnectionName(): ?string
    {
        // @mago-expect lint:inline-variable-return
        /** @var ?string $connection */
        $connection = config('phpinnacle-comments.connection', parent::getConnectionName());

        return $connection;
    }

    /**
     * @param Builder<static> $query
     * @return Builder<static>
     */
    public function scopeForSubject(Builder $query, Model $record): Builder
    {
        return $query->where([
            'subject_type' => $record->getMorphClass(),
            'subject_id' => $record->getKey(),
        ]);
    }
}

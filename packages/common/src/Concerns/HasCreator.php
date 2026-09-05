<?php

namespace PHPinnacle\Common\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * @mixin Model
 *
 * @property string $created_by
 * @property-read Model $creator
 */
trait HasCreator
{
    public static function bootHasCreator(): void
    {
        static::creating(function (self $model) {
            $model->created_by ??= Auth::id();
        });

        static::addGlobalScope(function (Builder $builder) {
            $builder->with('creator');
        });
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function creator(): BelongsTo
    {
        /** @var class-string<Model> $model */
        $model = config('auth.providers.users.model');

        return $this->belongsTo($model, 'created_by');
    }
}

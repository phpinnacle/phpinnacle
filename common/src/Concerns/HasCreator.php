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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by');
    }
}

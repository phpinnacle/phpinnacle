<?php

namespace PHPinnacle\Eidos\Tests\Fixtures;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class LookupCategory extends Model
{
    protected $connection = 'lookup';

    protected $table = 'lookup_categories';

    protected $primaryKey = 'category_id';

    protected static function booted(): void
    {
        static::addGlobalScope('listed', fn (Builder $query) => $query->where('listed', true));
    }
}

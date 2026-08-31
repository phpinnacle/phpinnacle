<?php

namespace PHPinnacle\Chronica\Concerns;

use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

trait HasHistory
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName(static::class)
            ->dontLogEmptyChanges()
            ->logFillable()
            ->logOnlyDirty()
            ->logExcept($this->getExcludedAttributes());
    }

    private function getExcludedAttributes(): array
    {
        return (array) config('phpinnacle-chronica.excluded_attributes', []);
    }
}

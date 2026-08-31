<?php

namespace PHPinnacle\Chronica\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Spatie\Activitylog\Models\Activity as BaseActivity;

/**
 * @property string $id
 */
class Activity extends BaseActivity
{
    use HasUuids;

    public function getConnectionName(): ?string
    {
        return config('phpinnacle-chronica.connection', parent::getConnectionName());
    }
}

<?php

namespace PHPinnacle\Cerber\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Laravel\Sanctum\PersonalAccessToken as SanctumToken;

class AccessToken extends SanctumToken
{
    use HasUuids;

    public function getConnectionName(): ?string
    {
        return config('phpinnacle-cerber.connection', parent::getConnectionName());
    }
}

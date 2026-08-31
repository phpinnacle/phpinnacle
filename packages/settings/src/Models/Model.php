<?php

namespace PHPinnacle\Settings\Models;

use Illuminate\Database\Eloquent\Model as BaseModel;

class Model extends BaseModel
{
    public function getConnectionName(): ?string
    {
        return config('phpinnacle-settings.connection', parent::getConnectionName());
    }
}

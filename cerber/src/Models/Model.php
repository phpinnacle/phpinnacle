<?php

namespace PHPinnacle\Cerber\Models;

use Illuminate\Database\Eloquent\Model as BaseModel;

class Model extends BaseModel
{
    public function getConnectionName(): ?string
    {
        return config('phpinnacle-cerber.connection', parent::getConnectionName());
    }
}

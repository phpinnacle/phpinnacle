<?php

namespace PHPinnacle\Midas\Models;

use Illuminate\Database\Eloquent\Model as BaseModel;

class Model extends BaseModel
{
    public function getConnectionName(): ?string
    {
        return config('phpinnacle-midas.connection', parent::getConnectionName());
    }
}

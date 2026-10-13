<?php

namespace PHPinnacle\Eidos\Contracts;

use Illuminate\Database\Eloquent\Model;

interface ProvidesEloquentModels
{
    /** @return list<class-string<Model>> */
    public function modelClasses(): array;
}

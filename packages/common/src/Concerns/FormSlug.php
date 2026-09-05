<?php

namespace PHPinnacle\Common\Concerns;

use Closure;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Operation;
use Illuminate\Support\Str;

class FormSlug
{
    public static function make(string $field = 'slug', ?Operation $only = Operation::Create): Closure
    {
        return function (Get $get, Set $set, ?string $old, ?string $state, string $operation) use ($field, $only) {
            if ($only !== null && $operation !== $only->value) {
                return;
            }

            if (($get($field) ?? '') !== Str::slug($old)) {
                return;
            }

            $set($field, Str::slug($state));
        };
    }
}

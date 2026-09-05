<?php

namespace PHPinnacle\Casus\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum HttpMethod: string implements HasColor, HasLabel
{
    case Get = 'GET';
    case Post = 'POST';
    case Put = 'PUT';
    case Patch = 'PATCH';

    case Delete = 'DELETE';

    public function getLabel(): string
    {
        return $this->value;
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Get => 'success',
            self::Post => 'info',
            self::Put, self::Patch => 'warning',
            self::Delete => 'danger',
        };
    }
}

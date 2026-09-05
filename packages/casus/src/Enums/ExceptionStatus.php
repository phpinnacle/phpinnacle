<?php

namespace PHPinnacle\Casus\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ExceptionStatus: string implements HasColor, HasLabel
{
    case Open = 'open';
    case Acknowledged = 'acknowledged';
    case Resolved = 'resolved';

    public function getColor(): string
    {
        return match ($this) {
            self::Open => 'danger',
            self::Acknowledged => 'warning',
            self::Resolved => 'success',
        };
    }

    public function getLabel(): string
    {
        return __("phpinnacle-casus::resources.exception.statuses.{$this->value}");
    }
}

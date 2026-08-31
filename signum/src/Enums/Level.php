<?php

namespace PHPinnacle\Signum\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum Level: string implements HasColor, HasIcon, HasLabel
{
    case Note = 'note';
    case Info = 'info';
    case Success = 'success';
    case Warning = 'warning';
    case Danger = 'danger';

    public function getLabel(): string
    {
        return __('phpinnacle-signum::enums.level.' . $this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Note => 'gray',
            self::Info => 'info',
            self::Success => 'success',
            self::Warning => 'warning',
            self::Danger => 'danger',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Note => 'phosphor-note',
            self::Info => 'phosphor-info',
            self::Success => 'phosphor-check-circle',
            self::Warning => 'phosphor-warning-circle',
            self::Danger => 'phosphor-biohazard',
        };
    }
}

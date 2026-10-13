<?php

namespace PHPinnacle\Midas\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum EripNotification: string implements HasColor, HasIcon, HasLabel
{
    case SMS = 'sms';
    case EMAIL = 'email';

    public function getColor(): array
    {
        return match ($this) {
            self::SMS => Color::Green,
            self::EMAIL => Color::Blue,
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::SMS => 'phosphor-sim-card',
            self::EMAIL => 'phosphor-at',
        };
    }

    public function getLabel(): string
    {
        return __('phpinnacle-midas::enums.erip_notification.' . $this->value);
    }
}

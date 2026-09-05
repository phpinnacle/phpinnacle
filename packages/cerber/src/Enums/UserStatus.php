<?php

namespace PHPinnacle\Cerber\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum UserStatus: string implements HasColor, HasIcon, HasLabel
{
    case Active = 'active';
    case Blocked = 'blocked';
    case Archived = 'archived';

    public function canAccess(): bool
    {
        return $this === self::Active;
    }

    public function getLabel(): string
    {
        return __('phpinnacle-cerber::enums.user_status.' . $this->value);
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Active => 'phosphor-check-circle',
            self::Blocked => 'phosphor-x-circle',
            self::Archived => 'phosphor-archive',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Active => 'primary',
            self::Blocked => 'danger',
            self::Archived => 'gray',
        };
    }
}

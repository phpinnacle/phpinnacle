<?php

namespace PHPinnacle\Cerber\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ProviderScope: string implements HasColor, HasLabel
{
    case Session = 'session';
    case Stateless = 'stateless';
    case Bff = 'bff';

    public function getLabel(): string
    {
        return match ($this) {
            self::Session => __('phpinnacle-cerber::resources.provider.scopes.session'),
            self::Stateless => __('phpinnacle-cerber::resources.provider.scopes.stateless'),
            self::Bff => __('phpinnacle-cerber::resources.provider.scopes.bff'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Session => 'primary',
            self::Stateless => 'success',
            self::Bff => 'gray',
        };
    }
}

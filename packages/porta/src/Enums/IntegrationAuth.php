<?php

namespace PHPinnacle\Porta\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum IntegrationAuth: string implements HasColor, HasLabel
{
    case None = 'none';
    case Header = 'header';
    case Query = 'query';

    public function getLabel(): string
    {
        return __(sprintf('phpinnacle-porta::enums.integration_auth.%s.label', $this->value));
    }

    public function getColor(): array
    {
        return match ($this) {
            self::None => Color::Gray,
            self::Header => Color::Green,
            self::Query => Color::Blue,
        };
    }
}

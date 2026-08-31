<?php

namespace PHPinnacle\Porta\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum IntegrationResponse: string implements HasColor, HasLabel
{
    case Empty = 'empty';
    case Model = 'model';
    case Identity = 'identity';

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Empty => Color::Gray,
            self::Model => Color::Blue,
            self::Identity => Color::Purple,
        };
    }

    public function getLabel(): ?string
    {
        return __(sprintf('phpinnacle-porta::enums.integration_response.%s.label', $this->value));
    }
}

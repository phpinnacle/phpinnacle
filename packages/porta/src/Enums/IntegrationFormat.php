<?php

namespace PHPinnacle\Porta\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum IntegrationFormat: string implements HasColor, HasLabel
{
    case JSON = 'json';

    public function getLabel(): ?string
    {
        return __(sprintf('phpinnacle-porta::enums.integration_format.%s.label', $this->value));
    }

    public function getColor(): string|array|null
    {
        return match ($this) { self::JSON => Color::Amber };
    }

    public function decode(string $payload): array
    {
        return match ($this) {
            self::JSON => (array) json_decode($payload, associative: true, flags: JSON_THROW_ON_ERROR),
        };
    }
}

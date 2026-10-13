<?php

namespace PHPinnacle\Midas\Payments;

use Filament\Support\Colors\Color;
use PHPinnacle\Midas\Enums\Ability;

class Cash extends Base
{
    public function key(): string
    {
        return 'cash';
    }

    public function getColor(): array
    {
        return Color::Green;
    }

    public function getIcon(): string
    {
        return 'phosphor-money';
    }

    public function getDescription(): string
    {
        return __('phpinnacle-midas::providers.cash.description');
    }

    public function getLabel(): string
    {
        return __('phpinnacle-midas::providers.cash.label');
    }

    public function abilities(): array
    {
        return [
            Ability::Offline,
        ];
    }
}

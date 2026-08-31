<?php

namespace PHPinnacle\Porta\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum WebhookStatus: string implements HasColor, HasLabel
{
    case Scheduled = 'scheduled';
    case Progress = 'progress';
    case Completed = 'completed';
    case Failed = 'failed';

    public function getColor(): array
    {
        return match ($this) {
            self::Scheduled => Color::Blue,
            self::Progress => Color::Orange,
            self::Completed => Color::Green,
            self::Failed => Color::Red,
        };
    }

    public function getLabel(): string
    {
        return __(sprintf('phpinnacle-porta::enums.webhook_status.%s.label', $this->value));
    }
}

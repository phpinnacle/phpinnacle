<?php

namespace PHPinnacle\Intl\Forms;

use Filament\Forms\Components\Select;
use PHPinnacle\Intl\Timezones;

class TimezonePicker extends Select
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('phpinnacle-intl::forms.timezone.label'))
            ->placeholder(__('phpinnacle-intl::forms.timezone.placeholder'))
            ->prefixIcon('phosphor-clock-user')
            ->searchable()
            ->options(Timezones::list())
            ->default(Timezones::DEFAULT)
            ->optionsLimit(500);
    }
}

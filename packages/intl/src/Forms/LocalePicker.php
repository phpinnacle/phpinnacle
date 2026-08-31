<?php

namespace PHPinnacle\Intl\Forms;

use Filament\Forms\Components\Select;
use PHPinnacle\Intl\Locales;

class LocalePicker extends Select
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('phpinnacle-intl::forms.locale.label'))
            ->placeholder(__('phpinnacle-intl::forms.locale.placeholder'))
            ->prefixIcon('phosphor-translate')
            ->searchable()
            ->searchValues(false)
            ->searchDebounce(200)
            ->options(Locales::list())
            ->default(Locales::default())
            ->optionsLimit(500);
    }
}

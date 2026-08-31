<?php

namespace PHPinnacle\Intl\Forms;

use Filament\Forms\Components\Select;
use PHPinnacle\Intl\Languages;

class LanguagePicker extends Select
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('phpinnacle-intl::forms.language.label'))
            ->placeholder(__('phpinnacle-intl::forms.language.placeholder'))
            ->prefixIcon('phosphor-translate')
            ->searchable()
            ->searchValues(false)
            ->searchDebounce(200)
            ->options(Languages::list())
            ->default(Languages::OTHER)
            ->optionsLimit(500);
    }
}

<?php

namespace PHPinnacle\Intl\Forms;

use Filament\Forms\Components\Select;
use PHPinnacle\Intl\Countries;

class CountryPicker extends Select
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('phpinnacle-intl::forms.country.label'))
            ->placeholder(__('phpinnacle-intl::forms.country.placeholder'))
            ->prefixIcon('phosphor-globe-simple')
            ->allowHtml()
            ->searchable()
            ->searchValues(false)
            ->searchDebounce(200)
            ->options(Countries::list())
            ->default(Countries::OTHER)
            ->optionsLimit(500);
    }
}

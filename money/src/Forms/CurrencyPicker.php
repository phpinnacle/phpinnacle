<?php

namespace PHPinnacle\Money\Forms;

use Filament\Forms\Components\Select;
use PHPinnacle\Money\Currencies;

class CurrencyPicker extends Select
{
    public static function getDefaultName(): string
    {
        return 'currency';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('phpinnacle-money::forms.currency.label'))
            ->placeholder(__('phpinnacle-money::forms.currency.placeholder'))
            ->prefixIcon('phosphor-coins')
            ->allowHtml()
            ->searchable()
            ->searchValues(false)
            ->searchDebounce(200)
            ->options(Currencies::list())
            ->optionsLimit(500);
    }
}

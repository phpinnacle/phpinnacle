<?php

namespace PHPinnacle\Money\Tables;

use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Number;
use PHPinnacle\Money\Money;

class MoneyColumn extends TextColumn
{
    public function setUp(): void
    {
        parent::setUp();

        $this->formatStateUsing(
            fn (?Money $state) => $state === null
                ? null
                : Number::currency(
                    number: $state->amount / (10 ** $state->subunit()),
                    in: $state->currency,
                    locale: $this->getTable()->getDefaultNumberLocale() ?? config('app.locale'),
                ),
        );
    }
}

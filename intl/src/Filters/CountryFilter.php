<?php

namespace PHPinnacle\Intl\Filters;

use Filament\Tables\Filters\SelectFilter;
use PHPinnacle\Intl\Countries;

class CountryFilter extends SelectFilter
{
    public static function getDefaultName(): ?string
    {
        return 'country';
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->options(Countries::list());
        $this->label(__('phpinnacle-intl::tables.filters.country.label'));
        $this->placeholder(__('phpinnacle-intl::tables.filters.country.placeholder'));
    }
}

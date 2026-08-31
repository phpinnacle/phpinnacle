<?php

namespace PHPinnacle\Intl\Filters;

use Filament\Tables\Filters\SelectFilter;
use PHPinnacle\Intl\Languages;

class LanguageFilter extends SelectFilter
{
    public static function getDefaultName(): ?string
    {
        return 'language';
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->options(Languages::list());
        $this->label(__('phpinnacle-intl::tables.filters.language.label'));
        $this->placeholder(__('phpinnacle-intl::tables.filters.language.placeholder'));
    }
}

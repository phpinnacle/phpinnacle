<?php

namespace PHPinnacle\Common\Filters;

use Filament\Tables\Filters\TernaryFilter;

class ActiveFilter extends TernaryFilter
{
    public static function getDefaultName(): string
    {
        return 'is_active';
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->label(__('phpinnacle-common::tables.filters.active.label'));
    }
}

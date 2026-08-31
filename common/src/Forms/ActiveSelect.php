<?php

namespace PHPinnacle\Common\Forms;

use Filament\Forms\Components\Select;

class ActiveSelect extends Select
{
    public static function getDefaultName(): string
    {
        return 'is_active';
    }

    public function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('phpinnacle-common::forms.active.label'))
            ->boolean()
            ->default(true)
            ->selectablePlaceholder(false);
    }
}

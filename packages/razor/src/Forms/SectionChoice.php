<?php

namespace PHPinnacle\Razor\Forms;

use Filament\Forms\Components\Select;
use PHPinnacle\Razor\Services\Registry;

class SectionChoice extends Select
{
    public function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('phpinnacle-razor::forms.section.label'))
            ->prefixIcon('phosphor-paint-brush-broad')
            ->options(fn (Registry $registry) => array_column($registry->all(), 'label', 'key'))
            ->preload();
    }
}

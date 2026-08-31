<?php

namespace PHPinnacle\Pinax\Schemas;

use Filament\Infolists\Components\ImageEntry;

class MediaEntry extends ImageEntry
{
    public function setUp(): void
    {
        parent::setUp();

        $this
            ->hiddenLabel()
            ->defaultImageUrl(url('images/placeholder.png'))
            ->alignCenter();
    }
}

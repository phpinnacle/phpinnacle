<?php

namespace PHPinnacle\Cerber\Forms;

use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;

class AvatarUpload extends FileUpload
{
    public function setUp(): void
    {
        parent::setUp();

        $this
            ->inlineLabel(false)
            ->hiddenLabel()
            ->disk('public')
            ->directory(sprintf('/avatars/%s', Filament::hasTenancy() ? Filament::getTenant()?->getKey() : ''))
            ->avatar()
            ->imageEditor()
            ->circleCropper()
            ->alignLeft()
            ->grow(false);
    }
}

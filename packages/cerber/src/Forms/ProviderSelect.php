<?php

namespace PHPinnacle\Cerber\Forms;

use Filament\Forms\Components\Select;
use PHPinnacle\Cerber\AuthProvider;
use PHPinnacle\Cerber\Services\ProviderRegistry;

class ProviderSelect extends Select
{
    public static function getDefaultName(): string
    {
        return 'provider';
    }

    public function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('phpinnacle-cerber::forms.provider.label'))
            ->placeholder(__('phpinnacle-cerber::forms.provider.placeholder'))
            ->options(fn (ProviderRegistry $registry) => $registry->all()->mapWithKeys(
                fn (AuthProvider $provider) => [$provider->getClass() => $provider->getLabel()],
            ));
    }
}

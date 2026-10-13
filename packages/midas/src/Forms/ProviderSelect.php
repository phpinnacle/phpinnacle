<?php

namespace PHPinnacle\Midas\Forms;

use Filament\Forms\Components\Select;
use PHPinnacle\Midas\Contracts\PaymentProvider;
use PHPinnacle\Midas\Services\ProviderRegistry;

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
            ->label(__('phpinnacle-midas::forms.provider.label'))
            ->placeholder(__('phpinnacle-midas::forms.provider.placeholder'))
            ->options(
                fn (ProviderRegistry $registry) => $registry
                    ->all()
                    ->map(fn (PaymentProvider $provider) => $provider->getLabel())
                    ->all(),
            )
            ->required();
    }
}

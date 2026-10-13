<?php

namespace PHPinnacle\Midas\Forms;

use Filament\Forms\Components\Select;
use PHPinnacle\Midas\Models\PaymentMethod;
use PHPinnacle\Midas\Resources\Methods\MethodResource;

class MethodSelect extends Select
{
    private bool $withDefault = false;

    private ?bool $online = null;

    public static function getDefaultName(): string
    {
        return 'method_id';
    }

    public function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('phpinnacle-midas::forms.payment_method.label'))
            ->placeholder(__('phpinnacle-midas::forms.payment_method.placeholder'))
            ->prefixIcon(MethodResource::getNavigationIcon())
            ->options(fn () => PaymentMethod::list($this->online))
            ->default(fn () => $this->withDefault ? PaymentMethod::default($this->online)?->id : null)
            ->required();
    }

    public function table(): self
    {
        return $this->prefixIcon(null);
    }

    public function online(bool $online): self
    {
        $this->online = $online;

        return $this;
    }

    public function withDefault(): self
    {
        $this->withDefault = true;

        return $this;
    }
}

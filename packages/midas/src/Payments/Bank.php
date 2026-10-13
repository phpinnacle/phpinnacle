<?php

namespace PHPinnacle\Midas\Payments;

use Filament\Forms\Components\TextInput;
use Filament\Support\Colors\Color;
use PHPinnacle\Midas\Enums\Ability;
use PHPinnacle\Midas\Models\PaymentMethod;

class Bank extends Base
{
    public function key(): string
    {
        return 'bank';
    }

    public function getColor(): array
    {
        return Color::Blue;
    }

    public function getIcon(): string
    {
        return 'phosphor-bank';
    }

    public function getDescription(): string
    {
        return __('phpinnacle-midas::providers.bank.description');
    }

    public function getLabel(): string
    {
        return __('phpinnacle-midas::providers.bank.label');
    }

    public function validate(array $settings): bool
    {
        return ($settings['name'] ?? null) !== null && ($settings['account'] ?? null) !== null;
    }

    public function define(array $settings = []): PaymentMethod
    {
        $method = PaymentMethod::define($this, $settings);
        $method->name = $settings['name'];

        return $method;
    }

    public function form(): array
    {
        return [
            TextInput::make('name')
                ->label(__('phpinnacle-midas::providers.bank.fields.name'))
                ->required()
                ->maxLength(250)
                ->visibleOn('create'),
            TextInput::make('account')
                ->label(__('phpinnacle-midas::providers.bank.fields.account'))
                ->required()
                ->maxLength(250),
        ];
    }

    public function abilities(): array
    {
        return [
            Ability::Offline,
        ];
    }
}

<?php

namespace PHPinnacle\Midas\Payments;

use BackedEnum;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\JsonSchema\Types\Type;
use LogicException;
use PHPinnacle\Midas\Contracts\PaymentProvider;
use PHPinnacle\Midas\Models\Continuation;
use PHPinnacle\Midas\Models\Intent;
use PHPinnacle\Midas\Models\Notification;
use PHPinnacle\Midas\Models\Payer;
use PHPinnacle\Midas\Models\PaymentMethod;

abstract class Base implements PaymentProvider
{
    public function getColor(): string|array|null
    {
        return 'primary';
    }

    public function getDescription(): string|Htmlable|null
    {
        return null;
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return null;
    }

    public function getLabel(): string|Htmlable|null
    {
        return null;
    }

    public function validate(array $settings): bool
    {
        return true;
    }

    public function define(array $settings = []): PaymentMethod
    {
        return PaymentMethod::define($this, $settings);
    }

    public function form(): array
    {
        return [];
    }

    public function abilities(): array
    {
        return [];
    }

    public function schema(PaymentMethod $method, Payer $payer): ?Type
    {
        return null;
    }

    public function intent(Intent $intent): Continuation
    {
        return Continuation::pending();
    }

    public function handle(Notification $notification): Continuation
    {
        throw new LogicException('This provider does not implement payment notifications.');
    }
}

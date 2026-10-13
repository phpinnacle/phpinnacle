<?php

namespace PHPinnacle\Midas\Contracts;

use Illuminate\JsonSchema\Types\Type;
use PHPinnacle\Midas\Enums\Ability;
use PHPinnacle\Midas\Models\Continuation;
use PHPinnacle\Midas\Models\Intent;
use PHPinnacle\Midas\Models\Notification;
use PHPinnacle\Midas\Models\Payer;
use PHPinnacle\Midas\Models\PaymentMethod;

interface PaymentGateway
{
    public function key(): string;

    /** @return list<Ability> */
    public function abilities(): array;

    public function schema(PaymentMethod $method, Payer $payer): ?Type;

    public function intent(Intent $intent): Continuation;

    public function handle(Notification $notification): Continuation;
}

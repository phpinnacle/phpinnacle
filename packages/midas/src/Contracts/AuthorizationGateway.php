<?php

namespace PHPinnacle\Midas\Contracts;

use PHPinnacle\Midas\Models\Continuation;
use PHPinnacle\Midas\Models\Intent;
use PHPinnacle\Midas\Models\Transaction;

interface AuthorizationGateway extends PaymentGateway
{
    public function authorize(Intent $intent): Continuation;

    public function capture(Transaction $transaction): Continuation;

    public function void(Transaction $transaction): Continuation;
}

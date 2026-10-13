<?php

namespace PHPinnacle\Midas\Contracts;

use PHPinnacle\Midas\Models\Continuation;
use PHPinnacle\Midas\Models\Transaction;

interface RefundGateway extends PaymentGateway
{
    public function refund(Transaction $transaction): Continuation;
}

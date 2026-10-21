<?php

namespace PHPinnacle\Midas\Contracts;

use PHPinnacle\Midas\Enums\AmountMode;
use PHPinnacle\Midas\Enums\TransactionType;
use PHPinnacle\Midas\Models\Continuation;
use PHPinnacle\Midas\Models\Intent;
use PHPinnacle\Midas\Models\Transaction;

interface AuthorizationGateway extends PaymentGateway
{
    /** @param TransactionType::CAPTURE|TransactionType::VOID $operation */
    public function amountMode(TransactionType $operation): AmountMode;

    public function authorize(Intent $intent): Continuation;

    public function capture(Transaction $transaction): Continuation;

    public function void(Transaction $transaction): Continuation;
}

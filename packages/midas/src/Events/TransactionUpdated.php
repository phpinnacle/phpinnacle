<?php

namespace PHPinnacle\Midas\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use PHPinnacle\Midas\Models\Transaction;

final readonly class TransactionUpdated implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public Transaction $transaction,
    ) {}
}

<?php

namespace PHPinnacle\Midas\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use PHPinnacle\Midas\Enums\TransactionStatus;
use PHPinnacle\Midas\Models\Transaction;

final readonly class TransactionStatusChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public Transaction $transaction,
        public TransactionStatus $previousStatus,
        public TransactionStatus $status,
    ) {}
}

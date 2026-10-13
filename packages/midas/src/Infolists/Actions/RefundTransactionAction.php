<?php

namespace PHPinnacle\Midas\Infolists\Actions;

use PHPinnacle\Midas\Enums\TransactionType;

class RefundTransactionAction extends TransactionOperationAction
{
    protected static function operation(): TransactionType
    {
        return TransactionType::REFUND;
    }
}

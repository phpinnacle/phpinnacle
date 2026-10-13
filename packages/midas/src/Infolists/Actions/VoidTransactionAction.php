<?php

namespace PHPinnacle\Midas\Infolists\Actions;

use PHPinnacle\Midas\Enums\TransactionType;

class VoidTransactionAction extends TransactionOperationAction
{
    protected static function operation(): TransactionType
    {
        return TransactionType::VOID;
    }
}

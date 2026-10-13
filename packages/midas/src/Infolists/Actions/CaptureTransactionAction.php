<?php

namespace PHPinnacle\Midas\Infolists\Actions;

use PHPinnacle\Midas\Enums\TransactionType;

class CaptureTransactionAction extends TransactionOperationAction
{
    protected static function operation(): TransactionType
    {
        return TransactionType::CAPTURE;
    }
}

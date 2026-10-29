<?php

namespace PHPinnacle\Ferry\Events;

use PHPinnacle\Ferry\Enums\ChangeOperation;

final readonly class RecordDeleted extends RecordChanged
{
    public function operation(): ChangeOperation
    {
        return ChangeOperation::Delete;
    }
}

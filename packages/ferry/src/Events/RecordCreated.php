<?php

namespace PHPinnacle\Ferry\Events;

use PHPinnacle\Ferry\Enums\ChangeOperation;

final readonly class RecordCreated extends RecordChanged
{
    public function operation(): ChangeOperation
    {
        return ChangeOperation::Create;
    }
}

<?php

namespace PHPinnacle\Langcat\Enum;

enum AssignType: string
{
    case Assigned = 'assigned';
    case Booked = 'booked';
    case Waiting = 'waiting';
    case Discharged = 'discharged';
    case Offered = 'offered';
}

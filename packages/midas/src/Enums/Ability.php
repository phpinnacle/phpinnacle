<?php

namespace PHPinnacle\Midas\Enums;

enum Ability: string
{
    case Offline = 'offline';
    case Online = 'online';
    case Recurring = 'recurring';
    case Refund = 'refund';
}

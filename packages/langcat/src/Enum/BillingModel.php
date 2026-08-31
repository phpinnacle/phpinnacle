<?php

namespace PHPinnacle\Langcat\Enum;

enum BillingModel: string
{
    case Whole = 'whole';
    case Hour = 'hour';
    case HourInAdvance = 'hour-in-advance';
}

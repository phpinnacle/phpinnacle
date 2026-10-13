<?php

namespace PHPinnacle\Midas\Enums;

enum AdjustmentType: string
{
    case Discount = 'discount';
    case Shipping = 'shipping';
    case Rounding = 'rounding';
    case Tax = 'tax';
    case Fee = 'fee';
}

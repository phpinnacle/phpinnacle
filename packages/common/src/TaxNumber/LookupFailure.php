<?php

namespace PHPinnacle\Common\TaxNumber;

enum LookupFailure: string
{
    case InvalidNumber = 'enter_valid_number';
    case NotFound = 'not_found';
    case Unavailable = 'unavailable';
}

<?php

namespace PHPinnacle\Canvio\Enum;

enum EnrollmentTask: string
{
    case Conclude = 'conclude';
    case Deactivate = 'deactivate';
    case Delete = 'delete';
    case Inactivate = 'inactivate';
}

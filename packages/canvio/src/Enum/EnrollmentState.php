<?php

namespace PHPinnacle\Canvio\Enum;

enum EnrollmentState: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Invited = 'invited';
}

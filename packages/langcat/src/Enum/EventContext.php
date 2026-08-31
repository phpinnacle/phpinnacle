<?php

namespace PHPinnacle\Langcat\Enum;

enum EventContext: string
{
    case Student = 'student';
    case Group = 'group';
    case StudentInGroup = 'studentInGroup';
}

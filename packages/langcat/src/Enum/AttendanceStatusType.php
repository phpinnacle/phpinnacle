<?php

namespace PHPinnacle\Langcat\Enum;

enum AttendanceStatusType: string
{
    case Present = 'present';
    case Absent = 'absent';
    case NotCounted = 'notCounted';
}

<?php

namespace PHPinnacle\Canvio\Enum;

enum CourseUserSort: string
{
    case Email = 'email';
    case LastLogin = 'last_login';
    case SisId = 'sis_id';
    case Username = 'username';
}

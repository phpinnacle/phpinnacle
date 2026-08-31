<?php

namespace PHPinnacle\Langcat\Enum;

enum UserRole: string
{
    case Student = 'student';
    case Teacher = 'teacher';
    case Parent = 'parent';
    case Company = 'company';
    case Administrator = 'administrator';
    case Superadministrator = 'superadministrator';
}

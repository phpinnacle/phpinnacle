<?php

namespace PHPinnacle\Canvio\Enum;

enum EnrollmentRole: string
{
    case Designer = 'designer';
    case Observer = 'observer';
    case Student = 'student';
    case Teacher = 'teacher';
    case TeachingAssistant = 'ta';
}

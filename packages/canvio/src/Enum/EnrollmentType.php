<?php

namespace PHPinnacle\Canvio\Enum;

enum EnrollmentType: string
{
    case Designer = 'DesignerEnrollment';
    case Observer = 'ObserverEnrollment';
    case Student = 'StudentEnrollment';
    case Teacher = 'TeacherEnrollment';
    case TeachingAssistant = 'TaEnrollment';
}

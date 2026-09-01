<?php

namespace PHPinnacle\Canvio\Enum;

enum AssignmentGradingType: string
{
    case GpaScale = 'gpa_scale';
    case LetterGrade = 'letter_grade';
    case NotGraded = 'not_graded';
    case PassFail = 'pass_fail';
    case Percent = 'percent';
    case Points = 'points';
}

<?php

namespace PHPinnacle\Langcat\Enum;

enum LessonStatusType: string
{
    case Clear = 'clear';
    case None = 'none';
    case Substitution = 'substitution';
    case Cancelled = 'cancelled';
    case Scheduled = 'scheduled';
    case Free = 'free';
    case Deleted = 'deleted';
}

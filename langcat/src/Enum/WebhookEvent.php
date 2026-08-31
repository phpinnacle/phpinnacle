<?php

namespace PHPinnacle\Langcat\Enum;

enum WebhookEvent: string
{
    case StudentCreated = 'student.created';
    case StudentActivated = 'student.activated';
    case StudentRegistered = 'student.registered';
    case LessonStatusChanged = 'lesson.status.changed';
    case LessonAttendanceChanged = 'lesson.attendance.changed';
    case StudentInGroupAssignTypeModified = 'studentInGroup.assignType.modified';
    case StudentInGroupAttendanceChecked = 'studentInGroup.attendance.checked';
    case DocumentSigned = 'document.signed';
}

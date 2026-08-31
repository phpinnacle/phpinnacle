<?php

namespace PHPinnacle\Langcat\Enum;

enum OnlineLessonProvider: string
{
    case Zoom = 'Zoom';
    case ClickMeeting = 'ClickMeeting';
    case BigBlueButton = 'BigBlueButton';
    case MeetingLink = 'MeetingLink';
    case MicrosoftTeams = 'MicrosoftTeams';
}

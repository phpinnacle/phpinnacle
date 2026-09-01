<?php

namespace PHPinnacle\Canvio\Enum;

enum SubmissionType: string
{
    case BasicLtiLaunch = 'basic_lti_launch';
    case MediaRecording = 'media_recording';
    case OnlineTextEntry = 'online_text_entry';
    case OnlineUpload = 'online_upload';
    case OnlineUrl = 'online_url';
    case StudentAnnotation = 'student_annotation';
}

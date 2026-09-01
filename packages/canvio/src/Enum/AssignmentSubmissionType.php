<?php

namespace PHPinnacle\Canvio\Enum;

enum AssignmentSubmissionType: string
{
    case DiscussionTopic = 'discussion_topic';
    case ExternalTool = 'external_tool';
    case MediaRecording = 'media_recording';
    case None = 'none';
    case OnPaper = 'on_paper';
    case OnlineQuiz = 'online_quiz';
    case OnlineTextEntry = 'online_text_entry';
    case OnlineUpload = 'online_upload';
    case OnlineUrl = 'online_url';
    case StudentAnnotation = 'student_annotation';
}

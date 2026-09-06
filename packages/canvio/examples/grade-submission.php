<?php

use PHPinnacle\Canvio\Client;
use PHPinnacle\Canvio\Request\Submissions\UpdateSubmissionRequest;

if ($argc !== 6) {
    fwrite(
        STDERR,
        "Usage: php examples/grade-submission.php <course-id> <assignment-id> <user-id> <grade> <comment>\n",
    );
    exit(1);
}

/** @var Client $canvas */
$canvas = require __DIR__ . '/bootstrap.php';

// This replaces the posted grade and adds feedback to the selected student's submission.
$submission = $canvas
    ->course($argv[1])
    ->assignment($argv[2])
    ->submission($argv[3])
    ->update(new UpdateSubmissionRequest(
        postedGrade: $argv[4],
        textComment: $argv[5],
    ));

echo
    json_encode([
        'assignment_id' => $submission->assignmentId,
        'user_id' => $submission->userId,
        'state' => $submission->workflowState,
        'grade' => $submission->grade,
        'score' => $submission->score,
    ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . "\n"
;

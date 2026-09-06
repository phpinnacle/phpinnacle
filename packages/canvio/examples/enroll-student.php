<?php

use PHPinnacle\Canvio\Client;
use PHPinnacle\Canvio\Enum\EnrollmentState;
use PHPinnacle\Canvio\Enum\EnrollmentType;
use PHPinnacle\Canvio\Request\Enrollments\CreateEnrollmentRequest;

if ($argc !== 3) {
    fwrite(STDERR, "Usage: php examples/enroll-student.php <course-id> <user-id>\n");
    exit(1);
}

/** @var Client $canvas */
$canvas = require __DIR__ . '/bootstrap.php';

// Both IDs may use SIS prefixes. The user must already exist in Canvas.
$enrollment = $canvas
    ->course($argv[1])
    ->enroll(new CreateEnrollmentRequest(
        userId: $argv[2],
        type: EnrollmentType::Student,
        state: EnrollmentState::Active,
        notify: false,
    ));

echo
    json_encode([
        'id' => $enrollment->id,
        'course_id' => $enrollment->courseId,
        'user_id' => $enrollment->userId,
        'state' => $enrollment->enrollmentState,
    ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . "\n"
;

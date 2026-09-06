<?php

use PHPinnacle\Canvio\Client;
use PHPinnacle\Canvio\Enum\EnrollmentRole;
use PHPinnacle\Canvio\Request\Users\ListCourseUsersRequest;

if ($argc !== 2) {
    fwrite(STDERR, "Usage: php examples/course-roster.php <course-id>\n");
    exit(1);
}

/** @var Client $canvas */
$canvas = require __DIR__ . '/bootstrap.php';

// The course identifier can also be a Canvas SIS ID, such as sis_course_id:PHY-101.
foreach ($canvas
    ->course($argv[1])
    ->users(new ListCourseUsersRequest(
        enrollmentTypes: [EnrollmentRole::Student],
        perPage: 100,
    )) as $student) {
    echo
        json_encode([
            'id' => $student->id,
            'name' => $student->name,
            'sis_user_id' => $student->sisUserId,
        ], JSON_THROW_ON_ERROR) . "\n"
    ;
}

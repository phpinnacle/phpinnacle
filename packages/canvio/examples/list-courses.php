<?php

use PHPinnacle\Canvio\Client;
use PHPinnacle\Canvio\Request\Courses\ListCoursesRequest;

/** @var Client $canvas */
$canvas = require __DIR__ . '/bootstrap.php';

// Iteration requests each next page lazily, even for accounts with many courses.
foreach ($canvas->courses(new ListCoursesRequest(include: ['term'], perPage: 100)) as $course) {
    echo
        json_encode([
            'id' => $course->id,
            'name' => $course->name,
            'code' => $course->courseCode,
            'state' => $course->workflowState,
            'term' => $course->raw['term'] ?? null,
        ], JSON_THROW_ON_ERROR) . "\n"
    ;
}

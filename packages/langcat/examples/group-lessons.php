<?php

use PHPinnacle\Langcat\Client;
use PHPinnacle\Langcat\Request\Groups\ListLessonsRequest;

if ($argc !== 4 || ($groupId = filter_var($argv[1], FILTER_VALIDATE_INT, ['options' => [
    'min_range' => 1,
]])) === false) {
    fwrite(STDERR, "Usage: php examples/group-lessons.php <positive-group-id> <from-date> <until-date>\n");
    exit(1);
}

/** @var Client $client */
$client = require __DIR__ . '/bootstrap.php';

// The start boundary is inclusive; the end boundary is exclusive.
$request = ListLessonsRequest::make()
    ->startsOnOrAfter($argv[2])
    ->startsBefore($argv[3])
    ->sortBy('+id')
    ->perPage(100);

$page = 1;

do {
    $lessons = $client->groups()->lessons($groupId, $request->page($page));

    foreach ($lessons->data as $lesson) {
        echo
            json_encode([
                'id' => $lesson->id,
                'start_at' => $lesson->startAt,
                'end_at' => $lesson->endAt,
                'teacher_ids' => $lesson->teachers,
                'classroom_id' => $lesson->classroomId,
                'visible' => $lesson->isVisible,
            ], JSON_THROW_ON_ERROR) . "\n"
        ;
    }

    ++$page;
} while ($page <= ceil($lessons->meta->total / 100));

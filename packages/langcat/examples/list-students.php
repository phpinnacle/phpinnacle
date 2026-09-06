<?php

use PHPinnacle\Langcat\Client;
use PHPinnacle\Langcat\Enum\StudentType;
use PHPinnacle\Langcat\Request\Students\ListStudentsRequest;

if ($argc !== 2 || ($schoolId = filter_var($argv[1], FILTER_VALIDATE_INT, ['options' => [
    'min_range' => 1,
]])) === false) {
    fwrite(STDERR, "Usage: php examples/list-students.php <positive-school-id>\n");
    exit(1);
}

/** @var Client $client */
$client = require __DIR__ . '/bootstrap.php';

$request = ListStudentsRequest::make()
    ->schoolId($schoolId)
    ->type(StudentType::Regular)
    ->archived(false)
    ->sortBy('+id')
    ->perPage(100);

// Langcat returns one page at a time. Use meta.total to request the remaining pages.
$page = 1;

do {
    $students = $client->students()->all($request->page($page));

    foreach ($students->data as $student) {
        echo
            json_encode([
                'id' => $student->id,
                'name' => $student->name,
                'last_name' => $student->lastName,
                'school_ids' => $student->schoolIds,
            ], JSON_THROW_ON_ERROR) . "\n"
        ;
    }

    ++$page;
} while ($page <= ceil($students->meta->total / 100));

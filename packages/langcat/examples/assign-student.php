<?php

use PHPinnacle\Langcat\Client;
use PHPinnacle\Langcat\Enum\AssignType;
use PHPinnacle\Langcat\Request\Groups\AssignStudentToGroupRequest;

if (
    $argc !== 4
    || ($groupId = filter_var($argv[1], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) === false
    || ($studentId = filter_var($argv[2], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) === false
) {
    fwrite(
        STDERR,
        "Usage: php examples/assign-student.php <positive-group-id> <positive-student-id> <enrolled-date>\n",
    );
    exit(1);
}

/** @var Client $client */
$client = require __DIR__ . '/bootstrap.php';

// The group and student must already exist. The enrollment date uses YYYY-MM-DD.
$client->groups()->assignStudent(
    $groupId,
    AssignStudentToGroupRequest::make()
        ->studentId($studentId)
        ->assignType(AssignType::Assigned)
        ->enrolledDate($argv[3]),
);

// This endpoint can succeed without a response body, so report the submitted identifiers.
echo
    json_encode([
        'group_id' => $groupId,
        'student_id' => $studentId,
        'assign_type' => AssignType::Assigned->value,
        'enrolled_date' => $argv[3],
    ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . "\n"
;

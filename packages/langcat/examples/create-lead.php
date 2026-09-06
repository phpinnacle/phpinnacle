<?php

use PHPinnacle\Langcat\Client;
use PHPinnacle\Langcat\Enum\StudentType;
use PHPinnacle\Langcat\Request\Students\CreateStudentRequest;

if ($argc !== 4 || ($schoolId = filter_var($argv[1], FILTER_VALIDATE_INT, ['options' => [
    'min_range' => 1,
]])) === false) {
    fwrite(STDERR, "Usage: php examples/create-lead.php <positive-school-id> <first-name> <last-name>\n");
    exit(1);
}

/** @var Client $client */
$client = require __DIR__ . '/bootstrap.php';

// Each run creates a new lead without parent accounts in the selected school.
$result = $client
    ->students()
    ->create(
        CreateStudentRequest::make()
            ->schoolId($schoolId)
            ->firstName($argv[2])
            ->lastName($argv[3])
            ->type(StudentType::Lead)
            ->hasParentAccounts(false),
    );

echo json_encode(['student_id' => $result->id], JSON_THROW_ON_ERROR) . "\n";

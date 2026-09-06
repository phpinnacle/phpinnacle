<?php

use PHPinnacle\Langcat\Client;

if ($argc !== 2 || ($studentId = filter_var($argv[1], FILTER_VALIDATE_INT, ['options' => [
    'min_range' => 1,
]])) === false) {
    fwrite(STDERR, "Usage: php examples/student-profile.php <positive-student-id>\n");
    exit(1);
}

/** @var Client $client */
$client = require __DIR__ . '/bootstrap.php';

// Identity and contact details are separate API resources.
$student = $client->students()->get($studentId);
$details = $client->students()->details($studentId);

echo
    json_encode([
        'id' => $student->id,
        'name' => $student->name,
        'last_name' => $student->lastName,
        'type' => $student->type,
        'archived' => $student->isArchived,
        'school_ids' => $student->schoolIds,
        'email' => $details->personal?->email,
        'phone' => $details->personal?->phone,
        'mobile' => $details->personal?->mobile,
    ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . "\n"
;

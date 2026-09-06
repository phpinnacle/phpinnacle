<?php

use PHPinnacle\Canvio\Client;
use PHPinnacle\Canvio\Enum\AssignmentGradingType;
use PHPinnacle\Canvio\Enum\AssignmentSubmissionType;
use PHPinnacle\Canvio\Request\Assignments\CreateAssignmentRequest;

if ($argc !== 2) {
    fwrite(STDERR, "Usage: php examples/create-assignment.php <course-id>\n");
    exit(1);
}

/** @var Client $canvas */
$canvas = require __DIR__ . '/bootstrap.php';

// Each run creates a new draft. Publish it in Canvas after reviewing its settings.
$assignment = $canvas
    ->course($argv[1])
    ->createAssignment(new CreateAssignmentRequest(
        name: 'Lab report: conservation of energy',
        description: '<p>Upload a PDF report with your measurements, calculations, and conclusions.</p>',
        pointsPossible: 20,
        gradingType: AssignmentGradingType::Points,
        submissionTypes: [AssignmentSubmissionType::OnlineUpload],
        allowedExtensions: ['pdf'],
        dueAt: new DateTimeImmutable('+7 days', new DateTimeZone('UTC')),
        published: false,
    ));

echo
    json_encode([
        'id' => $assignment->id,
        'name' => $assignment->name,
        'due_at' => $assignment->dueAt,
        'published' => $assignment->published,
        'url' => $assignment->htmlUrl,
    ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . "\n"
;

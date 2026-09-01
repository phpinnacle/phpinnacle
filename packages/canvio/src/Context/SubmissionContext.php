<?php

namespace PHPinnacle\Canvio\Context;

use PHPinnacle\Canvio\Request\Submissions\UpdateSubmissionRequest;
use PHPinnacle\Canvio\Response\SubmissionResponse;
use PHPinnacle\Canvio\Support\ResourcePath;
use PHPinnacle\Canvio\Support\Transport;

final readonly class SubmissionContext
{
    private string $path;

    public function __construct(
        private Transport $transport,
        int|string $courseId,
        int|string $assignmentId,
        int|string $userId,
    ) {
        $this->path =
            '/courses/'
            . ResourcePath::segment($courseId, 'Course ID')
            . '/assignments/'
            . ResourcePath::segment($assignmentId, 'Assignment ID')
            . '/submissions/'
            . ResourcePath::segment($userId, 'User ID');
    }

    public function details(): SubmissionResponse
    {
        return SubmissionResponse::fromArray($this->transport->send('GET', $this->path));
    }

    public function update(UpdateSubmissionRequest $request): SubmissionResponse
    {
        return SubmissionResponse::fromArray($this->transport->send('PUT', $this->path, body: $request->toArray()));
    }
}

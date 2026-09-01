<?php

namespace PHPinnacle\Canvio\Context;

use Generator;
use PHPinnacle\Canvio\Request\Assignments\UpdateAssignmentRequest;
use PHPinnacle\Canvio\Request\Submissions\ListSubmissionsRequest;
use PHPinnacle\Canvio\Request\Submissions\SubmitSubmissionRequest;
use PHPinnacle\Canvio\Response\AssignmentResponse;
use PHPinnacle\Canvio\Response\SubmissionResponse;
use PHPinnacle\Canvio\Support\ResourcePath;
use PHPinnacle\Canvio\Support\Transport;

final readonly class AssignmentContext
{
    private string $path;

    public function __construct(
        private Transport $transport,
        private int|string $courseId,
        private int|string $assignmentId,
    ) {
        $this->path =
            '/courses/'
            . ResourcePath::segment($courseId, 'Course ID')
            . '/assignments/'
            . ResourcePath::segment($assignmentId, 'Assignment ID');
    }

    public function delete(): AssignmentResponse
    {
        return AssignmentResponse::fromArray($this->transport->send('DELETE', $this->path));
    }

    public function details(): AssignmentResponse
    {
        return AssignmentResponse::fromArray($this->transport->send('GET', $this->path));
    }

    public function submission(int|string $userId): SubmissionContext
    {
        return new SubmissionContext($this->transport, $this->courseId, $this->assignmentId, $userId);
    }

    /** @return Generator<int, SubmissionResponse> */
    public function submissions(?ListSubmissionsRequest $request = null): Generator
    {
        foreach ($this->transport->paginate($this->path . '/submissions', $request?->toQuery() ?? []) as $payload) {
            yield SubmissionResponse::fromArray($payload);
        }
    }

    public function submit(SubmitSubmissionRequest $request): SubmissionResponse
    {
        return SubmissionResponse::fromArray($this->transport->send(
            'POST',
            $this->path . '/submissions',
            body: $request->toArray(),
        ));
    }

    public function update(UpdateAssignmentRequest $request): AssignmentResponse
    {
        return AssignmentResponse::fromArray($this->transport->send('PUT', $this->path, body: $request->toArray()));
    }
}

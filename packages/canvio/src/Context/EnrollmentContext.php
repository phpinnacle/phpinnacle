<?php

namespace PHPinnacle\Canvio\Context;

use PHPinnacle\Canvio\Enum\EnrollmentTask;
use PHPinnacle\Canvio\Response\EnrollmentResponse;
use PHPinnacle\Canvio\Support\ResourcePath;
use PHPinnacle\Canvio\Support\Transport;

final readonly class EnrollmentContext
{
    private string $path;

    public function __construct(
        private Transport $transport,
        int|string $courseId,
        int|string $enrollmentId,
    ) {
        $this->path =
            '/courses/'
            . ResourcePath::segment($courseId, 'Course ID')
            . '/enrollments/'
            . ResourcePath::segment($enrollmentId, 'Enrollment ID');
    }

    public function end(EnrollmentTask $task = EnrollmentTask::Conclude): EnrollmentResponse
    {
        return EnrollmentResponse::fromArray($this->transport->send('DELETE', $this->path, ['task' => $task]));
    }
}
